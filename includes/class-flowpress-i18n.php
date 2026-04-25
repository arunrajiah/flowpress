<?php
/**
 * Define the internationalisation functionality.
 *
 * Loads and defines the internationalisation files for this plugin so that it
 * is ready for translation.
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
 * Class FlowPress_i18n
 *
 * Responsible for loading the plugin text domain so that WordPress can serve
 * translated strings from the /languages directory.
 *
 * @since 0.1.0
 */
class FlowPress_i18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * This method is hooked onto `plugins_loaded` so that the text domain is
	 * always loaded after WordPress is fully initialised. Translation files
	 * are expected to live at:
	 *
	 *   wp-content/languages/plugins/flowpress-{locale}.mo
	 *
	 * or inside the plugin's own languages/ directory:
	 *
	 *   wp-content/plugins/flowpress/languages/flowpress-{locale}.mo
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'flowpress',
			false,
			dirname( FLOWPRESS_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
