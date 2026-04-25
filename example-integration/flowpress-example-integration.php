<?php
/**
 * Plugin Name:       FlowPress Example Integration
 * Plugin URI:        https://github.com/flowpress/flowpress
 * Description:       Demonstrates how to extend FlowPress with a custom trigger (CF7) and a custom action (Slack). Use this as a starting template for your own integrations.
 * Version:           0.1.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            FlowPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fp-example-integration
 *
 * @package FP_Example_Integration
 *
 * ─── HOW TO USE THIS EXAMPLE ──────────────────────────────────────────────────
 *
 * This plugin is a self-contained demonstration. To create your own integration:
 *
 *   1. Copy this directory and rename it (e.g. my-flowpress-integration).
 *   2. Update the Plugin Name, Text Domain, and @package tag above.
 *   3. Replace the example trigger/action classes with your own.
 *   4. Activate your plugin — FlowPress will pick up your classes automatically.
 *
 * Read docs/DEVELOPERS.md for the full API reference.
 * ──────────────────────────────────────────────────────────────────────────────
 */

// Abort if accessed directly.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Bootstrap: load dependencies and register with FlowPress.
 *
 * We hook on 'plugins_loaded' so FlowPress is guaranteed to be active before
 * we try to extend it. The actual registration happens via the
 * 'flowpress_register_triggers' / 'flowpress_register_actions' hooks which
 * FlowPress fires on 'init' (priority 20).
 */
add_action( 'plugins_loaded', 'fp_example_integration_bootstrap' );

/**
 * Load class files and register extension hooks.
 *
 * @since 0.1.0
 * @return void
 */
function fp_example_integration_bootstrap(): void {
	// Bail gracefully when FlowPress is not active.
	if ( ! class_exists( 'FlowPress_Abstract_Trigger' ) ) {
		add_action(
			'admin_notices',
			static function () {
				echo '<div class="notice notice-error"><p>'
					. esc_html__( 'FlowPress Example Integration requires FlowPress to be installed and active.', 'fp-example-integration' )
					. '</p></div>';
			}
		);
		return;
	}

	require_once __DIR__ . '/includes/class-fp-example-trigger-cf7.php';
	require_once __DIR__ . '/includes/class-fp-example-action-slack.php';

	// ── Register the custom trigger ────────────────────────────────────────────
	//
	// 'flowpress_register_triggers' receives the registry class name as $registry.
	// Call $registry::register() with an instance of your trigger class.
	//
	add_action(
		'flowpress_register_triggers',
		static function ( string $registry ): void {
			$registry::register( new FP_Example_Trigger_CF7() );
		}
	);

	// ── Register the custom action ─────────────────────────────────────────────
	//
	// Same pattern as triggers, but using 'flowpress_register_actions'.
	//
	add_action(
		'flowpress_register_actions',
		static function ( string $registry ): void {
			$registry::register( new FP_Example_Action_Slack() );
		}
	);
}
