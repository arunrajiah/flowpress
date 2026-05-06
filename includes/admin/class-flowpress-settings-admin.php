<?php
/**
 * Settings admin page — renders and saves the FlowPress settings form.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/admin
 * @since      0.2.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Settings_Admin
 *
 * @since 0.2.0
 */
class FlowPress_Settings_Admin {

	/**
	 * Register hooks.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'handle_save' ) );
	}

	/**
	 * Handle the settings form POST on admin_init (before HTML output).
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function handle_save(): void {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		if ( 'flowpress-settings' !== $page ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'flowpress_save_settings' );

		FlowPress_Settings::update(
			array(
				'from_name'          => isset( $_POST['fp_from_name'] )          ? wp_unslash( $_POST['fp_from_name'] )          : '',
				'from_email'         => isset( $_POST['fp_from_email'] )         ? wp_unslash( $_POST['fp_from_email'] )         : '',
				'log_retention_days' => isset( $_POST['fp_log_retention_days'] ) ? wp_unslash( $_POST['fp_log_retention_days'] ) : 30,
				'max_retry_attempts' => isset( $_POST['fp_max_retry_attempts'] ) ? wp_unslash( $_POST['fp_max_retry_attempts'] ) : 4,
			)
		);

		wp_safe_redirect( add_query_arg( 'fp_notice', 'settings_saved', admin_url( 'admin.php?page=flowpress-settings' ) ) );
		exit;
	}

	/**
	 * Render the settings page.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'flowpress' ) );
		}

		require_once FLOWPRESS_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
	}
}
