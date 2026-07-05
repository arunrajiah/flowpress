<?php
/**
 * Trigger: Scheduled (time-based / cron).
 *
 * Fires recipes on a recurring schedule — hourly, twice daily, daily, or
 * weekly. A single WP-Cron event fires every hour; it compares each
 * enabled scheduled recipe's frequency against its last-run timestamp
 * and executes the ones that are due.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/triggers
 * @since      0.2.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Trigger_Scheduled
 *
 * @since 0.2.0
 */
class FlowPress_Trigger_Scheduled extends FlowPress_Abstract_Trigger {

	/**
	 * WP-Cron action hook name.
	 *
	 * @since 0.2.0
	 * @var   string
	 */
	const CRON_HOOK = 'flowpress_scheduled_tick';

	/**
	 * Post meta key that stores the last-run Unix timestamp per recipe.
	 *
	 * @since 0.2.0
	 * @var   string
	 */
	const META_LAST_RUN = '_fp_scheduled_last_run';

	/**
	 * Minimum interval in seconds for each frequency option.
	 *
	 * @since 0.2.0
	 * @var   array<string, int>
	 */
	private static $intervals = array(
		'hourly'     => HOUR_IN_SECONDS,
		'twicedaily' => 12 * HOUR_IN_SECONDS,
		'daily'      => DAY_IN_SECONDS,
		'weekly'     => WEEK_IN_SECONDS,
	);

	// ── Abstract method implementations ───────────────────────────────────────

	/** @inheritDoc */
	public function get_type(): string {
		return 'scheduled';
	}

	/** @inheritDoc */
	public function get_label(): string {
		return __( 'Scheduled', 'flowpress' );
	}

	/** @inheritDoc */
	public function get_description(): string {
		return __( 'Fires on a repeating schedule — hourly, daily, or weekly. Useful for digest emails, cleanup jobs, and recurring reports.', 'flowpress' );
	}

	/** @inheritDoc */
	public function get_icon(): string {
		return 'dashicons-clock';
	}

	/**
	 * Fields shown in the trigger-config section of the builder.
	 *
	 * These map to `trigger_config` in the recipe meta.
	 *
	 * @since  0.2.0
	 * @return array<int, array<string, mixed>>
	 */
	public function get_config_fields(): array {
		return array(
			array(
				'key'     => 'frequency',
				'label'   => __( 'Frequency', 'flowpress' ),
				'type'    => 'select',
				'options' => array(
					'hourly'     => __( 'Every hour', 'flowpress' ),
					'twicedaily' => __( 'Twice a day', 'flowpress' ),
					'daily'      => __( 'Once a day', 'flowpress' ),
					'weekly'     => __( 'Once a week', 'flowpress' ),
				),
				'default' => 'daily',
			),
		);
	}

	/** @inheritDoc */
	public function get_tokens(): array {
		return array(
			array( 'token' => 'current_date',     'label' => __( 'Current date (Y-m-d)', 'flowpress' ) ),
			array( 'token' => 'current_time',     'label' => __( 'Current time (H:i)', 'flowpress' ) ),
			array( 'token' => 'current_datetime', 'label' => __( 'Current date & time', 'flowpress' ) ),
			array( 'token' => 'site_name',        'label' => __( 'Site name', 'flowpress' ) ),
			array( 'token' => 'site_url',         'label' => __( 'Site URL', 'flowpress' ) ),
			array( 'token' => 'admin_email',      'label' => __( 'Admin email', 'flowpress' ) ),
		);
	}

	/** @inheritDoc */
	public function get_sample_payload(): array {
		return $this->build_payload();
	}

	/**
	 * Register the hourly WP-Cron tick and attach our processor.
	 *
	 * Called once via `FlowPress_Trigger_Registry::init()`.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function attach(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}

		add_action( self::CRON_HOOK, array( $this, 'tick' ) );
	}

	/**
	 * Backward-compatible alias for older callers.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function register_hook(): void {
		$this->attach();
	}

	// ── Cron tick ─────────────────────────────────────────────────────────────

	/**
	 * Fires every hour. Iterates enabled scheduled recipes and runs the
	 * ones that are due based on their frequency and last-run time.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function tick(): void {
		$now     = time();
		$recipes = $this->get_enabled_scheduled_recipes();

		foreach ( $recipes as $recipe ) {
			$config    = $recipe->get_trigger_config();
			$frequency = isset( $config['frequency'] ) && isset( self::$intervals[ $config['frequency'] ] )
				? $config['frequency']
				: 'daily';

			$last_run = (int) get_post_meta( $recipe->get_id(), self::META_LAST_RUN, true );
			$interval = self::$intervals[ $frequency ];

			if ( $last_run && ( $now - $last_run ) < $interval ) {
				continue; // Not due yet.
			}

			$payload = $this->build_payload();

			// Use execute_recipe() directly to run this specific recipe.
			FlowPress_Runner::execute_recipe( $recipe, $this->get_type(), $payload, false );

			update_post_meta( $recipe->get_id(), self::META_LAST_RUN, $now );
		}
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Build the token payload for the current moment.
	 *
	 * @since  0.2.0
	 * @return array<string, string>
	 */
	private function build_payload(): array {
		return array(
			'current_date'     => current_time( 'Y-m-d' ),
			'current_time'     => current_time( 'H:i' ),
			'current_datetime' => current_time( 'Y-m-d H:i:s' ),
			'site_name'        => get_bloginfo( 'name' ),
			'site_url'         => get_home_url(),
			'admin_email'      => get_option( 'admin_email' ),
		);
	}

	/**
	 * Query all enabled recipes whose trigger type is `scheduled`.
	 *
	 * @since  0.2.0
	 * @global wpdb $wpdb
	 * @return FlowPress_Recipe[]
	 */
	private function get_enabled_scheduled_recipes(): array {
		$query = new WP_Query(
			array(
				'post_type'      => 'flowpress_recipe',
				'post_status'    => FlowPress_Recipe::STATUS_ENABLED,
				'posts_per_page' => -1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_fp_trigger',
						'value' => 'scheduled',
					),
				),
				'no_found_rows'  => true,
				'fields'         => 'ids',
			)
		);

		$recipes = array();
		foreach ( $query->posts as $post_id ) {
			try {
				$recipes[] = new FlowPress_Recipe( (int) $post_id );
			} catch ( InvalidArgumentException $e ) {
				// Skip invalid recipes.
				continue;
			}
		}

		return $recipes;
	}

	// ── Static lifecycle helpers ───────────────────────────────────────────────

	/**
	 * Schedule the hourly tick if it isn't already scheduled.
	 * Called on plugin activation.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public static function schedule_tick(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Clear the hourly tick.
	 * Called on plugin deactivation.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public static function clear_tick(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}
}
