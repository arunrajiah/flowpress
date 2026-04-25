<?php
/**
 * Base class for all FlowPress triggers.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Abstract Class FlowPress_Abstract_Trigger
 *
 * Every trigger must extend this class and implement the abstract methods.
 * The trigger is responsible for:
 *   1. Declaring its machine name, human name, and the tokens it emits.
 *   2. Attaching itself to a WordPress hook via attach().
 *   3. Calling FlowPress_Runner::run() with a payload array when the event fires.
 *
 * @since 0.3.0
 */
abstract class FlowPress_Abstract_Trigger {

	/**
	 * Unique machine-readable type slug, e.g. "post_published".
	 *
	 * @since  0.3.0
	 * @return string
	 */
	abstract public function get_type();

	/**
	 * Human-readable name shown in the UI.
	 *
	 * @since  0.3.0
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * One-sentence description shown in the trigger catalogue.
	 *
	 * @since  0.3.0
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * Tokens this trigger emits, used to populate the placeholder picker.
	 * Returns an array of token_key => human label pairs.
	 *
	 * @since  0.3.0
	 * @return array<string, string>
	 */
	abstract public function get_tokens();

	/**
	 * Sample payload used for test-runs and documentation.
	 *
	 * @since  0.3.0
	 * @return array<string, mixed>
	 */
	abstract public function get_sample_payload();

	/**
	 * Dashicons class name for the trigger icon, e.g. "dashicons-bolt".
	 *
	 * @since  0.4.0
	 * @return string
	 */
	public function get_icon() {
		return 'dashicons-controls-repeat';
	}

	/**
	 * Serialise to an array for wp_localize_script / JSON.
	 *
	 * @since  0.4.0
	 * @return array
	 */
	public function to_array() {
		// get_tokens() returns [{token, label}] indexed array format.
		return array(
			'type'        => $this->get_type(),
			'label'       => $this->get_label(),
			'description' => $this->get_description(),
			'icon'        => $this->get_icon(),
			'tokens'      => $this->get_tokens(),
		);
	}

	/**
	 * Attach the trigger to a WordPress hook so it fires at the right moment.
	 *
	 * @since  0.3.0
	 * @return void
	 */
	abstract public function attach();
}
