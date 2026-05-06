<?php
/**
 * Settings page view.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/admin/views
 * @since      0.2.0
 *
 * @var FlowPress_Settings $settings (accessed via static methods)
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$s = FlowPress_Settings::get_all();
?>
<div class="wrap flowpress-wrap">

	<?php require_once FLOWPRESS_PLUGIN_DIR . 'includes/admin/views/promo-banner.php'; ?>

	<h1><?php esc_html_e( 'FlowPress Settings', 'flowpress' ); ?></h1>

	<?php if ( isset( $_GET['fp_notice'] ) && 'settings_saved' === $_GET['fp_notice'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved.', 'flowpress' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=flowpress-settings' ) ); ?>">
		<?php wp_nonce_field( 'flowpress_save_settings' ); ?>

		<!-- ── Email ─────────────────────────────────────────────────── -->
		<h2 class="fp-settings-section-title">
			<span class="dashicons dashicons-email-alt"></span>
			<?php esc_html_e( 'Email', 'flowpress' ); ?>
		</h2>
		<p class="fp-settings-section-desc">
			<?php esc_html_e( 'Default sender used by the "Send Email" action. Leave blank to use the WordPress site name and admin email.', 'flowpress' ); ?>
		</p>

		<table class="form-table fp-settings-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="fp_from_name"><?php esc_html_e( 'From Name', 'flowpress' ); ?></label>
				</th>
				<td>
					<input type="text"
					       id="fp_from_name"
					       name="fp_from_name"
					       value="<?php echo esc_attr( $s['from_name'] ); ?>"
					       class="regular-text"
					       placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					<p class="description">
						<?php
						printf(
							/* translators: %s: current site name. */
							esc_html__( 'Defaults to your site name: %s', 'flowpress' ),
							'<strong>' . esc_html( get_bloginfo( 'name' ) ) . '</strong>'
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="fp_from_email"><?php esc_html_e( 'From Email', 'flowpress' ); ?></label>
				</th>
				<td>
					<input type="email"
					       id="fp_from_email"
					       name="fp_from_email"
					       value="<?php echo esc_attr( $s['from_email'] ); ?>"
					       class="regular-text"
					       placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					<p class="description">
						<?php
						printf(
							/* translators: %s: current admin email. */
							esc_html__( 'Defaults to your admin email: %s', 'flowpress' ),
							'<strong>' . esc_html( get_option( 'admin_email' ) ) . '</strong>'
						);
						?>
					</p>
				</td>
			</tr>
		</table>

		<!-- ── Logs ──────────────────────────────────────────────────── -->
		<h2 class="fp-settings-section-title">
			<span class="dashicons dashicons-list-view"></span>
			<?php esc_html_e( 'Run Logs', 'flowpress' ); ?>
		</h2>
		<p class="fp-settings-section-desc">
			<?php esc_html_e( 'FlowPress logs every recipe execution. Auto-pruning keeps your database lean.', 'flowpress' ); ?>
		</p>

		<table class="form-table fp-settings-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="fp_log_retention_days"><?php esc_html_e( 'Log Retention', 'flowpress' ); ?></label>
				</th>
				<td>
					<input type="number"
					       id="fp_log_retention_days"
					       name="fp_log_retention_days"
					       value="<?php echo esc_attr( $s['log_retention_days'] ); ?>"
					       min="0"
					       max="3650"
					       class="small-text">
					<span class="fp-settings-unit"><?php esc_html_e( 'days', 'flowpress' ); ?></span>
					<p class="description">
						<?php esc_html_e( 'Run log entries older than this are deleted automatically. Set to 0 to keep logs forever.', 'flowpress' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<!-- ── Reliability ───────────────────────────────────────────── -->
		<h2 class="fp-settings-section-title">
			<span class="dashicons dashicons-update"></span>
			<?php esc_html_e( 'Reliability', 'flowpress' ); ?>
		</h2>
		<p class="fp-settings-section-desc">
			<?php esc_html_e( 'FlowPress automatically retries failed actions with exponential back-off.', 'flowpress' ); ?>
		</p>

		<table class="form-table fp-settings-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="fp_max_retry_attempts"><?php esc_html_e( 'Max Retry Attempts', 'flowpress' ); ?></label>
				</th>
				<td>
					<input type="number"
					       id="fp_max_retry_attempts"
					       name="fp_max_retry_attempts"
					       value="<?php echo esc_attr( $s['max_retry_attempts'] ); ?>"
					       min="1"
					       max="10"
					       class="small-text">
					<p class="description">
						<?php esc_html_e( 'How many times a failing action is retried before being marked permanently failed (1–10). Back-off delays: 1 min → 5 min → 30 min → 2 hr.', 'flowpress' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Settings', 'flowpress' ) ); ?>
	</form>

</div>
