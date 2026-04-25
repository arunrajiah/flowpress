<?php
/**
 * FlowPress
 *
 * @package           FlowPress
 * @author            FlowPress Contributors
 * @copyright         2024 FlowPress Contributors
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       FlowPress
 * Plugin URI:        https://github.com/flowpress/flowpress
 * Description:       A free, open-source WordPress plugin that automates "when X happens on my site, do Y" — a local, on-site automation engine. No subscriptions, no third-party servers.
 * Version:           0.1.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            FlowPress Contributors
 * Author URI:        https://github.com/flowpress/flowpress
 * Text Domain:       flowpress
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://github.com/flowpress/flowpress
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'FLOWPRESS_VERSION', '0.1.0' );

/**
 * The absolute path to the plugin directory, with trailing slash.
 */
define( 'FLOWPRESS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * The URL to the plugin directory, with trailing slash.
 */
define( 'FLOWPRESS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The absolute path to the main plugin file.
 */
define( 'FLOWPRESS_PLUGIN_FILE', __FILE__ );

/**
 * The plugin basename (e.g. flowpress/flowpress.php).
 */
define( 'FLOWPRESS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load the activation class and register the activation hook.
 *
 * @since 0.1.0
 */
function flowpress_activate() {
	require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-activator.php';
	FlowPress_Activator::activate();
}
register_activation_hook( __FILE__, 'flowpress_activate' );

/**
 * Load the deactivation class and register the deactivation hook.
 *
 * @since 0.1.0
 */
function flowpress_deactivate() {
	require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-deactivator.php';
	FlowPress_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'flowpress_deactivate' );

/**
 * Load the core plugin class and begin execution.
 *
 * @since 0.1.0
 */
function flowpress_run() {
	require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress.php';
	$plugin = FlowPress::get_instance();
	$plugin->run();
}
flowpress_run();
