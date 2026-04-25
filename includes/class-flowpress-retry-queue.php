<?php
/**
 * Retry queue — schedules and processes failed action retries via WP-Cron.
 *
 * Retry policy (documented for admins):
 *   - Attempt 1 (immediate): the action runs as part of the normal recipe execution.
 *   - Attempt 2: 1 minute after first failure.
 *   - Attempt 3: 5 minutes after second failure.
 *   - Attempt 4: 30 minutes after third failure.
 *   After 4 total attempts the action is marked permanently failed.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.5.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Retry_Queue
 *
 * @since 0.5.0
 */
class FlowPress_Retry_Queue {

	const TABLE_NAME  = 'flowpress_retry_queue';
	const CRON_HOOK   = 'flowpress_process_retry';
	const MAX_ATTEMPTS = 4;

	/** Delays in seconds indexed by attempt number (1-based attempt that failed). */
	const BACKOFF = array(
		1 => 60,
		2 => 300,
		3 => 1800,
	);

	// ── Schema ─────────────────────────────────────────────────────────────────

	/**
	 * Create the retry queue table on activation.
	 *
	 * @since  0.5.0
	 * @global wpdb $wpdb
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table           = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			run_log_id      BIGINT(20) UNSIGNED NOT NULL,
			recipe_id       BIGINT(20) UNSIGNED NOT NULL,
			trigger_type    VARCHAR(64)         NOT NULL DEFAULT '',
			trigger_payload LONGTEXT,
			action_index    SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
			action_type     VARCHAR(64)         NOT NULL DEFAULT '',
			action_config   LONGTEXT,
			attempt         TINYINT UNSIGNED    NOT NULL DEFAULT 1,
			max_attempts    TINYINT UNSIGNED    NOT NULL DEFAULT 4,
			status          VARCHAR(16)         NOT NULL DEFAULT 'pending',
			created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY run_log_id  (run_log_id),
			KEY status      (status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop the retry queue table on uninstall.
	 *
	 * @since  0.5.0
	 * @global wpdb $wpdb
	 * @return void
	 */
	public static function drop_table() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_NAME;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
	}

	// ── Queue management ───────────────────────────────────────────────────────

	/**
	 * Schedule a retry for a failed action.
	 *
	 * @since  0.5.0
	 * @param  int    $run_log_id      Original run log row ID.
	 * @param  int    $recipe_id       Recipe post ID.
	 * @param  string $trigger_type    Trigger type slug.
	 * @param  array  $trigger_payload Original trigger payload.
	 * @param  int    $action_index    Index of the failed action in the actions array.
	 * @param  string $action_type     Action type slug.
	 * @param  array  $action_config   Resolved action config (with placeholders already applied).
	 * @param  int    $current_attempt The attempt number that just failed (1 = first try).
	 * @global wpdb   $wpdb
	 * @return void
	 */
	public static function schedule( $run_log_id, $recipe_id, $trigger_type, array $trigger_payload, $action_index, $action_type, array $action_config, $current_attempt ) {
		global $wpdb;

		$next_attempt = $current_attempt + 1;

		if ( $next_attempt > self::MAX_ATTEMPTS ) {
			return; // Exhausted all retries.
		}

		$delay = self::BACKOFF[ $current_attempt ] ?? 1800;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . self::TABLE_NAME,
			array(
				'run_log_id'      => absint( $run_log_id ),
				'recipe_id'       => absint( $recipe_id ),
				'trigger_type'    => sanitize_key( $trigger_type ),
				'trigger_payload' => wp_json_encode( $trigger_payload ),
				'action_index'    => absint( $action_index ),
				'action_type'     => sanitize_key( $action_type ),
				'action_config'   => wp_json_encode( $action_config ),
				'attempt'         => absint( $next_attempt ),
				'max_attempts'    => self::MAX_ATTEMPTS,
				'status'          => 'pending',
				'created_at'      => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		$queue_id = (int) $wpdb->insert_id;

		wp_schedule_single_event( time() + $delay, self::CRON_HOOK, array( $queue_id ) );
	}

	/**
	 * Process a retry — called by WP-Cron.
	 *
	 * @since  0.5.0
	 * @param  int $queue_id Retry queue row ID.
	 * @global wpdb $wpdb
	 * @return void
	 */
	public static function process( $queue_id ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d AND status = 'pending'", absint( $queue_id ) ) );

		if ( ! $row ) {
			return; // Already processed or deleted.
		}

		// Mark as processing so concurrent cron runs don't double-fire.
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . self::TABLE_NAME,
			array( 'status' => 'processing' ),
			array( 'id' => $row->id ),
			array( '%s' ),
			array( '%d' )
		);

		$action = FlowPress_Action_Registry::get( $row->action_type );

		if ( ! $action ) {
			self::mark_done( $row->id, 'failed' );
			return;
		}

		$config  = json_decode( $row->action_config, true ) ?: array();
		$payload = json_decode( $row->trigger_payload, true ) ?: array();

		$result = $action->execute( $config, $payload, false );

		if ( $result->is_success() ) {
			self::mark_done( $row->id, 'completed' );
			self::update_run_log_action( (int) $row->run_log_id, (int) $row->action_index, $result, (int) $row->attempt );
		} else {
			self::mark_done( $row->id, 'failed' );
			self::update_run_log_action( (int) $row->run_log_id, (int) $row->action_index, $result, (int) $row->attempt );

			// Schedule another retry if attempts remain.
			self::schedule(
				(int) $row->run_log_id,
				(int) $row->recipe_id,
				$row->trigger_type,
				$payload,
				(int) $row->action_index,
				$row->action_type,
				$config,
				(int) $row->attempt
			);
		}
	}

	/**
	 * Update the run log's action_results array with the retry outcome.
	 *
	 * @since  0.5.0
	 * @param  int                     $run_log_id   Run log row ID.
	 * @param  int                     $action_index Index of the action in the results array.
	 * @param  FlowPress_Action_Result $result       Retry result.
	 * @param  int                     $attempt      Attempt number.
	 * @global wpdb                    $wpdb
	 * @return void
	 */
	private static function update_run_log_action( $run_log_id, $action_index, FlowPress_Action_Result $result, $attempt ) {
		global $wpdb;

		$log_table = $wpdb->prefix . FlowPress_Run_Log::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$log_table}` WHERE id = %d", $run_log_id ) );

		if ( ! $row ) {
			return;
		}

		$action_results = json_decode( $row->action_results, true ) ?: array();

		if ( isset( $action_results[ $action_index ] ) ) {
			$action_results[ $action_index ]['status']          = $result->get_status();
			$action_results[ $action_index ]['message']         = $result->get_message();
			$action_results[ $action_index ]['last_attempt']    = $attempt;
		}

		$overall = $result->is_success() ? FlowPress_Run_Log::STATUS_SUCCESS : FlowPress_Run_Log::STATUS_FAILED;

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$log_table,
			array(
				'action_results' => wp_json_encode( $action_results ),
				'status'         => $overall,
				'error_message'  => $result->is_success() ? '' : $result->get_message(),
			),
			array( 'id' => $run_log_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Mark a retry queue row as done.
	 *
	 * @since  0.5.0
	 * @param  int    $queue_id Queue row ID.
	 * @param  string $status   'completed' or 'failed'.
	 * @global wpdb   $wpdb
	 * @return void
	 */
	private static function mark_done( $queue_id, $status ) {
		global $wpdb;

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . self::TABLE_NAME,
			array( 'status' => $status ),
			array( 'id' => absint( $queue_id ) ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Get the human-readable retry policy description for admin display.
	 *
	 * @since  0.5.0
	 * @return string
	 */
	public static function get_policy_description() {
		return __( 'If an action fails, FlowPress will retry it up to 3 more times: after 1 minute, 5 minutes, and 30 minutes. If all retries fail, the run is marked as permanently failed and you can re-run it manually from the Runs log once the underlying problem is fixed.', 'flowpress' );
	}
}
