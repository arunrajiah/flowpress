<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation
 * and enforces minimum environment requirements before any other code runs.
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
 * Class FlowPress_Activator
 *
 * Handles all plugin activation logic including environment compatibility checks.
 *
 * @since 0.1.0
 */
class FlowPress_Activator {

	/**
	 * Minimum required PHP version.
	 *
	 * @since 0.1.0
	 * @var   string
	 */
	const MIN_PHP = '7.4';

	/**
	 * Minimum required WordPress version.
	 *
	 * @since 0.1.0
	 * @var   string
	 */
	const MIN_WP = '5.9';

	/**
	 * Activate the plugin.
	 *
	 * Runs environment checks before allowing activation. If requirements are
	 * not met the plugin is deactivated and an admin notice is displayed so the
	 * site owner knows exactly what needs to be upgraded.
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public static function activate() {
		self::check_requirements();

		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-audit-log.php';
		FlowPress_Audit_Log::create_table();

		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-run-log.php';
		FlowPress_Run_Log::create_table();

		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-retry-queue.php';
		FlowPress_Retry_Queue::create_table();

		// Register CPT and flush rewrite rules so our post type is recognised.
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-recipe-post-type.php';
		$cpt = new FlowPress_Recipe_Post_Type();
		$cpt->register_post_type();
		$cpt->register_post_statuses();
		flush_rewrite_rules();

		update_option( 'flowpress_version', FLOWPRESS_VERSION );
	}

	/**
	 * Check that the server meets the minimum PHP and WordPress requirements.
	 *
	 * If requirements are not met: deactivate the plugin and call wp_die()
	 * with a human-readable explanation.
	 *
	 * @since  0.1.0
	 * @access private
	 * @return void
	 */
	private static function check_requirements() {
		$errors = array();

		// PHP version check.
		if ( version_compare( PHP_VERSION, self::MIN_PHP, '<' ) ) {
			$errors[] = sprintf(
				/* translators: 1: Required PHP version, 2: Current PHP version. */
				__( 'FlowPress requires PHP %1$s or higher. Your server is running PHP %2$s.', 'flowpress' ),
				self::MIN_PHP,
				PHP_VERSION
			);
		}

		// WordPress version check.
		global $wp_version;
		if ( version_compare( $wp_version, self::MIN_WP, '<' ) ) {
			$errors[] = sprintf(
				/* translators: 1: Required WordPress version, 2: Current WordPress version. */
				__( 'FlowPress requires WordPress %1$s or higher. You are running WordPress %2$s.', 'flowpress' ),
				self::MIN_WP,
				$wp_version
			);
		}

		if ( ! empty( $errors ) ) {
			// Deactivate the plugin before showing the error so it does not
			// remain checked on the plugins screen.
			deactivate_plugins( FLOWPRESS_PLUGIN_BASENAME );

			$message = '<strong>' . __( 'FlowPress could not be activated.', 'flowpress' ) . '</strong>';
			$message .= '<ul>';
			foreach ( $errors as $error ) {
				$message .= '<li>' . esc_html( $error ) . '</li>';
			}
			$message .= '</ul>';

			wp_die(
				wp_kses_post( $message ),
				esc_html__( 'Plugin Activation Error', 'flowpress' ),
				array(
					'response'  => 200,
					'back_link' => true,
				)
			);
		}
	}
}
