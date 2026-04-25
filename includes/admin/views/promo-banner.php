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
		<span class="fp-promo-logo" aria-hidden="true">
			<span class="dashicons dashicons-controls-repeat"></span>
		</span>
		<div class="fp-promo-copy">
			<strong class="fp-promo-name">FlowPress</strong>
			<span class="fp-promo-tagline">
				<?php esc_html_e( 'Free &amp; open-source automation — built with ♥ by', 'flowpress' ); ?>
				<a href="https://github.com/arunrajiah" target="_blank" rel="noopener noreferrer">arunrajiah</a>
			</span>
		</div>
		<a href="https://github.com/sponsors/arunrajiah"
		   target="_blank"
		   rel="noopener noreferrer"
		   class="fp-promo-btn">
			<span class="fp-promo-btn-heart" aria-hidden="true">♥</span>
			<?php esc_html_e( 'Become a Sponsor', 'flowpress' ); ?>
		</a>
	</div>
</div>
