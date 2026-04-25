<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's
 * deactivation. Deactivation is not the same as uninstall; data is preserved
 * so the user's flows are restored if they reactivate.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @author     FlowPress Contributors
 * @license    GPL-2.0-or-later
 * @since      0.1.0
 */

// Abort if accessed directly.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Deactivator
 *
 * Handles all plugin deactivation logic.
 *
 * @since 0.1.0
 */
class FlowPress_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * On deactivation we intentionally keep all plugin data (options, custom
	 * post types, tables) intact so they are available if the plugin is
	 * reactivated. Data removal is handled exclusively by {@see uninstall.php}.
	 *
	 * Tasks to add in future phases:
	 *   - wp_clear_scheduled_hook() for any WP-Cron events registered by FlowPress.
	 *   - Flush rewrite rules if custom post types were registered.
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'flowpress_process_retry' );
		flush_rewrite_rules();
	}
}
