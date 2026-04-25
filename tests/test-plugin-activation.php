<?php
/**
 * Tests for plugin bootstrap and constants.
 *
 * @package FlowPress
 */

/**
 * Class Test_Plugin_Activation
 *
 * Verifies that the plugin loads correctly and that all expected constants and
 * classes are available after the plugin file is required.
 *
 * @since 0.1.0
 */
class Test_Plugin_Activation extends WP_UnitTestCase {

	/**
	 * FLOWPRESS_VERSION constant is defined and non-empty.
	 */
	public function test_version_constant_is_defined() {
		$this->assertTrue( defined( 'FLOWPRESS_VERSION' ) );
		$this->assertNotEmpty( FLOWPRESS_VERSION );
	}

	/**
	 * FLOWPRESS_PLUGIN_DIR constant is defined and points to a real directory.
	 */
	public function test_plugin_dir_constant_is_defined() {
		$this->assertTrue( defined( 'FLOWPRESS_PLUGIN_DIR' ) );
		$this->assertDirectoryExists( FLOWPRESS_PLUGIN_DIR );
	}

	/**
	 * FLOWPRESS_PLUGIN_URL constant is defined and looks like a URL.
	 */
	public function test_plugin_url_constant_is_defined() {
		$this->assertTrue( defined( 'FLOWPRESS_PLUGIN_URL' ) );
		$this->assertStringStartsWith( 'http', FLOWPRESS_PLUGIN_URL );
	}

	/**
	 * FLOWPRESS_PLUGIN_FILE constant is defined and points to a real file.
	 */
	public function test_plugin_file_constant_is_defined() {
		$this->assertTrue( defined( 'FLOWPRESS_PLUGIN_FILE' ) );
		$this->assertFileExists( FLOWPRESS_PLUGIN_FILE );
	}

	/**
	 * Core class FlowPress exists.
	 */
	public function test_core_class_exists() {
		$this->assertTrue( class_exists( 'FlowPress' ) );
	}

	/**
	 * FlowPress_Activator class exists.
	 */
	public function test_activator_class_exists() {
		$this->assertTrue( class_exists( 'FlowPress_Activator' ) );
	}

	/**
	 * FlowPress_Deactivator class exists.
	 */
	public function test_deactivator_class_exists() {
		$this->assertTrue( class_exists( 'FlowPress_Deactivator' ) );
	}

	/**
	 * FlowPress_i18n class exists.
	 */
	public function test_i18n_class_exists() {
		$this->assertTrue( class_exists( 'FlowPress_i18n' ) );
	}

	/**
	 * FlowPress::get_instance() returns the same singleton on repeated calls.
	 */
	public function test_singleton_returns_same_instance() {
		$instance_a = FlowPress::get_instance();
		$instance_b = FlowPress::get_instance();

		$this->assertSame( $instance_a, $instance_b );
	}

	/**
	 * get_version() returns the expected version string.
	 */
	public function test_get_version_matches_constant() {
		$instance = FlowPress::get_instance();
		$this->assertSame( FLOWPRESS_VERSION, $instance->get_version() );
	}

	/**
	 * get_plugin_name() returns the expected text domain.
	 */
	public function test_get_plugin_name_returns_text_domain() {
		$instance = FlowPress::get_instance();
		$this->assertSame( 'flowpress', $instance->get_plugin_name() );
	}
}
