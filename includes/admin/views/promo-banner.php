<?php
/**
 * Promotional banner shown at the top of every FlowPress admin screen.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/admin/views
 * @since      0.6.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="fp-promo-banner" role="banner">
	<div class="fp-promo-inner">

		<span class="fp-promo-icon" aria-hidden="true">
			<span class="dashicons dashicons-controls-repeat"></span>
		</span>

		<div class="fp-promo-body">
			<p class="fp-promo-line1">
				<strong class="fp-promo-name">FlowPress</strong>
				<?php
				printf(
					/* translators: %s: author link */
					esc_html__( 'is a free plugin developed and maintained by %s.', 'flowpress' ),
					'<a href="https://github.com/arunrajiah" target="_blank" rel="noopener noreferrer">arunrajiah</a>'
				);
				?>
			</p>
			<p class="fp-promo-line2">
				<?php esc_html_e( 'If you find it useful, please consider', 'flowpress' ); ?>
				<a href="https://github.com/sponsors/arunrajiah"
				   target="_blank"
				   rel="noopener noreferrer"
				   class="fp-promo-btn">
					<span class="fp-promo-btn-heart" aria-hidden="true">&#9829;</span><?php esc_html_e( 'becoming a sponsor on GitHub', 'flowpress' ); ?>
				</a>
				<?php esc_html_e( '— it helps keep the project alive and growing.', 'flowpress' ); ?>
			</p>
		</div>

	</div>
</div>
