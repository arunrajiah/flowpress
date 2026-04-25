<?php
/**
 * Recipe runner — resolves placeholders, evaluates conditions, executes actions.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Runner
 *
 * @since 0.3.0
 */
class FlowPress_Runner {

	/**
	 * Fire all enabled recipes that match $trigger_type.
	 *
	 * @since  0.3.0
	 * @param  string $trigger_type Trigger type slug.
	 * @param  array  $payload      Data emitted by the trigger.
	 * @param  bool   $dry_run      If true, simulate without side effects.
	 * @return void
	 */
	public static function run( $trigger_type, array $payload, $dry_run = false ) {
		$recipes = self::get_recipes_for_trigger( $trigger_type );

		foreach ( $recipes as $post ) {
			try {
				$recipe = new FlowPress_Recipe( $post );
			} catch ( InvalidArgumentException $e ) {
				continue;
			}

			if ( ! $recipe->is_enabled() ) {
				if ( ! $dry_run ) {
					FlowPress_Run_Log::insert(
						$recipe->get_id(),
						$trigger_type,
						$payload,
						array(),
						FlowPress_Run_Log::STATUS_SKIPPED,
						__( 'Recipe is disabled.', 'flowpress' ),
						false
					);
				}
				continue;
			}

			self::execute_recipe( $recipe, $trigger_type, $payload, $dry_run );
		}
	}

	/**
	 * Execute one recipe with the given trigger payload.
	 *
	 * Evaluates conditions first; schedules retries on action failure.
	 *
	 * @since  0.3.0
	 * @param  FlowPress_Recipe $recipe       Recipe to execute.
	 * @param  string           $trigger_type Trigger type slug.
	 * @param  array            $payload      Trigger payload.
	 * @param  bool             $dry_run      If true, simulate without side effects.
	 * @return array            Action results array.
	 */
	public static function execute_recipe( FlowPress_Recipe $recipe, $trigger_type, array $payload, $dry_run = false ) {

		// ── Conditions check ───────────────────────────────────────────────────
		$conditions = $recipe->get_conditions();

		if ( ! $dry_run && ! empty( $conditions['items'] ) ) {
			$passes = FlowPress_Condition_Evaluator::evaluate( $conditions, $payload );

			if ( ! $passes ) {
				FlowPress_Run_Log::insert(
					$recipe->get_id(),
					$trigger_type,
					$payload,
					array(),
					FlowPress_Run_Log::STATUS_SKIPPED,
					__( 'Conditions not met.', 'flowpress' ),
					false
				);
				return array();
			}
		}

		// ── Execute actions ────────────────────────────────────────────────────
		$action_results = array();
		$overall_status = FlowPress_Run_Log::STATUS_SUCCESS;
		$error_message  = '';

		foreach ( $recipe->get_actions() as $action_index => $action_config ) {
			$action_type = $action_config['type'] ?? '';
			$config      = $action_config['config'] ?? array();

			$action = FlowPress_Action_Registry::get( $action_type );

			if ( ! $action ) {
				$result = FlowPress_Action_Result::failed(
					sprintf(
						/* translators: %s: action type slug. */
						__( 'Action type "%s" is not registered.', 'flowpress' ),
						$action_type
					)
				);
				$action_results[] = array_merge( array( 'type' => $action_type ), $result->to_array() );
				$overall_status   = FlowPress_Run_Log::STATUS_FAILED;
				$error_message    = $result->get_message();
				continue;
			}

			$resolved_config = self::resolve_config( $config, $payload );
			$result          = $action->execute( $resolved_config, $payload, $dry_run );

			$action_results[] = array_merge( array( 'type' => $action_type, 'attempt' => 1 ), $result->to_array() );

			if ( ! $result->is_success() && FlowPress_Action_Result::STATUS_SKIPPED !== $result->get_status() ) {
				$overall_status = FlowPress_Run_Log::STATUS_FAILED;
				$error_message  = $result->get_message();
			}
		}

		$run_log_id = FlowPress_Run_Log::insert(
			$recipe->get_id(),
			$trigger_type,
			$payload,
			$action_results,
			$dry_run ? FlowPress_Run_Log::STATUS_DRY_RUN : $overall_status,
			$error_message,
			$dry_run
		);

		// ── Schedule retries for failed actions ────────────────────────────────
		if ( ! $dry_run && $run_log_id && FlowPress_Run_Log::STATUS_FAILED === $overall_status ) {
			foreach ( $recipe->get_actions() as $action_index => $action_config ) {
				$result_entry = $action_results[ $action_index ] ?? array();

				if ( ( $result_entry['status'] ?? '' ) === FlowPress_Action_Result::STATUS_FAILED ) {
					$resolved_config = self::resolve_config( $action_config['config'] ?? array(), $payload );

					FlowPress_Retry_Queue::schedule(
						$run_log_id,
						$recipe->get_id(),
						$trigger_type,
						$payload,
						$action_index,
						$action_config['type'] ?? '',
						$resolved_config,
						1 // first attempt just failed.
					);
				}
			}
		}

		return $action_results;
	}

	/**
	 * Re-run a specific run log entry using its original payload.
	 *
	 * @since  0.5.0
	 * @param  int $run_log_id Run log row ID.
	 * @return array|WP_Error  Action results or error.
	 */
	public static function rerun( $run_log_id ) {
		global $wpdb;

		$log_table = $wpdb->prefix . FlowPress_Run_Log::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$log_table}` WHERE id = %d", absint( $run_log_id ) ) );

		if ( ! $row ) {
			return new WP_Error( 'not_found', __( 'Run log entry not found.', 'flowpress' ) );
		}

		try {
			$recipe = new FlowPress_Recipe( (int) $row->recipe_id );
		} catch ( InvalidArgumentException $e ) {
			return new WP_Error( 'recipe_not_found', __( 'Recipe not found.', 'flowpress' ) );
		}

		$payload = json_decode( $row->trigger_payload, true ) ?: array();

		return self::execute_recipe( $recipe, $row->trigger_type, $payload, false );
	}

	// ── Helpers ────────────────────────────────────────────────────────────────

	/**
	 * Resolve placeholder tokens in all string config values.
	 *
	 * @since  0.3.0
	 * @param  array $config  Raw action config.
	 * @param  array $payload Trigger payload.
	 * @return array
	 */
	private static function resolve_config( array $config, array $payload ) {
		$resolved = array();
		foreach ( $config as $key => $value ) {
			$resolved[ $key ] = is_string( $value )
				? FlowPress_Placeholder::resolve( $value, $payload )
				: $value;
		}
		return $resolved;
	}

	/**
	 * Find all enabled/disabled recipes that match $trigger_type.
	 *
	 * @since  0.3.0
	 * @param  string $trigger_type Trigger type slug.
	 * @return WP_Post[]
	 */
	private static function get_recipes_for_trigger( $trigger_type ) {
		return get_posts(
			array(
				'post_type'      => FlowPress_Recipe_Post_Type::POST_TYPE,
				'post_status'    => array(
					FlowPress_Recipe::STATUS_ENABLED,
					FlowPress_Recipe::STATUS_DISABLED,
				),
				'posts_per_page' => -1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_flowpress_trigger',
						'value' => sanitize_key( $trigger_type ),
					),
				),
			)
		);
	}
}
