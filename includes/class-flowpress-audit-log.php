<?php
/**
 * Audit log for recipe changes.
 *
 * Stores a lightweight history of who changed a recipe and what changed,
 * using a custom table for efficient querying.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.2.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Audit_Log
 *
 * @since 0.2.0
 */
class FlowPress_Audit_Log {

	const TABLE_NAME = 'flowpress_audit_log';

	/**
	 * Create the audit log table on plugin activation.
	 *
	 * @since  0.2.0
	 * @global wpdb $wpdb
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			recipe_id   BIGINT(20) UNSIGNED NOT NULL,
			user_id     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			action      VARCHAR(64)         NOT NULL DEFAULT '',
			summary     TEXT,
			created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY recipe_id (recipe_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop the audit log table on plugin uninstall.
	 *
	 * @since  0.2.0
	 * @global wpdb $wpdb
	 * @return void
	 */
	public static function drop_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Record an event for a recipe.
	 *
	 * @since  0.2.0
	 * @param  int    $recipe_id Recipe post ID.
	 * @param  string $action    Short action slug, e.g. 'created', 'updated', 'status_changed'.
	 * @param  string $summary   Human-readable description of what changed.
	 * @global wpdb   $wpdb
	 * @return void
	 */
	public static function log( $recipe_id, $action, $summary = '' ) {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . self::TABLE_NAME,
			array(
				'recipe_id'  => absint( $recipe_id ),
				'user_id'    => get_current_user_id(),
				'action'     => sanitize_key( $action ),
				'summary'    => sanitize_textarea_field( $summary ),
				'created_at' => current_time( 'mysql', true ), // UTC.
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Retrieve audit log entries for a recipe.
	 *
	 * @since  0.2.0
	 * @param  int $recipe_id Recipe post ID.
	 * @param  int $limit     Maximum rows to return (default 50).
	 * @return array Array of row objects with id, recipe_id, user_id, action, summary, created_at.
	 * @global wpdb $wpdb
	 */
	public static function get_for_recipe( $recipe_id, $limit = 50 ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE recipe_id = %d ORDER BY created_at DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				absint( $recipe_id ),
				absint( $limit )
			)
		);
	}

	/**
	 * Delete all log entries for a recipe (called when a recipe is deleted).
	 *
	 * @since  0.2.0
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
