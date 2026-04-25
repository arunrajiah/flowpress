<?php
/**
 * Run log — persists the result of every recipe execution.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Run_Log
 *
 * @since 0.3.0
 */
class FlowPress_Run_Log {

	const TABLE_NAME = 'flowpress_run_log';

	const STATUS_SUCCESS = 'success';
	const STATUS_FAILED  = 'failed';
	const STATUS_SKIPPED = 'skipped';
	const STATUS_DRY_RUN = 'dry_run';

	/**
	 * Create the run log table.
	 *
	 * @since  0.3.0
	 * @global wpdb $wpdb
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			recipe_id     BIGINT(20) UNSIGNED NOT NULL,
			trigger_type  VARCHAR(64)         NOT NULL DEFAULT '',
			trigger_payload LONGTEXT,
			action_results  LONGTEXT,
			status        VARCHAR(16)         NOT NULL DEFAULT 'success',
			error_message TEXT,
			is_dry_run    TINYINT(1)          NOT NULL DEFAULT 0,
			created_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY   (id),
			KEY recipe_id  (recipe_id),
			KEY status     (status),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop the run log table.
	 *
	 * @since  0.3.0
	 * @global wpdb $wpdb
	 * @return void
	 */
	public static function drop_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );
	}

	/**
	 * Insert a run log entry.
	 *
	 * @since  0.3.0
	 * @param  int    $recipe_id       Recipe post ID.
	 * @param  string $trigger_type    Trigger type slug.
	 * @param  array  $trigger_payload The data the trigger emitted.
	 * @param  array  $action_results  Array of FlowPress_Action_Result::to_array() values.
	 * @param  string $status          One of STATUS_* constants.
	 * @param  string $error_message   Top-level error if the whole run failed.
	 * @param  bool   $is_dry_run      Whether this was a test/dry run.
	 * @global wpdb   $wpdb
	 * @return int Inserted row ID.
	 */
	public static function insert( $recipe_id, $trigger_type, array $trigger_payload, array $action_results, $status, $error_message = '', $is_dry_run = false ) {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . self::TABLE_NAME,
			array(
				'recipe_id'       => absint( $recipe_id ),
				'trigger_type'    => sanitize_key( $trigger_type ),
				'trigger_payload' => wp_json_encode( $trigger_payload ),
				'action_results'  => wp_json_encode( $action_results ),
				'status'          => sanitize_key( $status ),
				'error_message'   => sanitize_textarea_field( $error_message ),
				'is_dry_run'      => $is_dry_run ? 1 : 0,
				'created_at'      => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Retrieve run log rows for a recipe.
	 *
	 * @since  0.3.0
	 * @param  int $recipe_id Recipe post ID.
	 * @param  int $limit     Maximum rows.
	 * @global wpdb $wpdb
	 * @return array
	 */
	public static function get_for_recipe( $recipe_id, $limit = 30 ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE recipe_id = %d ORDER BY created_at DESC LIMIT %d",
				absint( $recipe_id ),
				absint( $limit )
			)
		);
	}

	/**
	 * Delete all run log rows for a recipe.
	 *
	 * @since  0.3.0
	 * @param  int $recipe_id Recipe post ID.
	 * @global wpdb $wpdb
	 * @return void
	 */
	public static function delete_for_recipe( $recipe_id ) {
		global $wpdb;

		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . self::TABLE_NAME,
			array( 'recipe_id' => absint( $recipe_id ) ),
			array( '%d' )
		);
	}
}
