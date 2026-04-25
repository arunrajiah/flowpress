<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static.
 * - Check for the WP_UNINSTALL_PLUGIN constant — abort otherwise.
 * - Remove ALL options, post meta, custom post types, capabilities, etc.
 *
 * @package FlowPress
 * @author  FlowPress Contributors
 * @license GPL-2.0-or-later
 * @since   0.1.0
 */

// If uninstall is not called from WordPress, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

/**
 * Remove all plugin data on uninstall.
 *
 * As FlowPress grows, add deletions here:
 *   - delete_option() calls for every option registered by the plugin.
 *   - $wpdb queries to drop any custom tables.
 *   - wp_clear_scheduled_hook() for any WP-Cron events.
 *
 * Network-wide uninstall (multisite): iterate over sites with get_sites()
 * and call switch_to_blog() / restore_current_blog() around each deletion.
 *
 * @since 0.1.0
 */

// Only remove data if the admin has opted in (checked during uninstall UI in a future phase).
// For now, always clean up to keep the site pristine on uninstall.

// --- Options ---
delete_option( 'flowpress_version' );

// --- Custom DB tables ---
require_once plugin_dir_path( __FILE__ ) . 'includes/class-flowpress-audit-log.php';
FlowPress_Audit_Log::drop_table();

require_once plugin_dir_path( __FILE__ ) . 'includes/class-flowpress-run-log.php';
FlowPress_Run_Log::drop_table();

require_once plugin_dir_path( __FILE__ ) . 'includes/class-flowpress-retry-queue.php';
FlowPress_Retry_Queue::drop_table();

// --- Scheduled hooks ---
// wp_clear_scheduled_hook( 'flowpress_run_queue' );
