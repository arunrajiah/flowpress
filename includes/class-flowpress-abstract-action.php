<?php
/**
 * Base class for all FlowPress actions.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Abstract Class FlowPress_Abstract_Action
 *
 * Every action must extend this class and implement the abstract methods.
 *
 * @since 0.3.0
 */
abstract class FlowPress_Abstract_Action {

	/**
	 * Unique machine-readable type slug, e.g. "send_email".
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
	 * One-sentence description shown in the action catalogue.
	 *
	 * @since  0.3.0
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * Field definitions for the action's configuration form.
	 *
	 * Returns an array of field definitions:
	 * [
	 *   'key'         => string   — used as the config array key,
	 *   'label'       => string   — shown in the form,
	 *   'type'        => string   — 'text'|'textarea'|'email',
	 *   'required'    => bool,
	 *   'placeholder' => string,
	 *   'help'        => string   — inline help text,
	 * ]
	 *
	 * @since  0.3.0
	 * @return array[]
	 */
	abstract public function get_fields();

	/**
	 * Dashicons class name for the action icon.
	 *
	 * @since  0.4.0
	 * @return string
	 */
	public function get_icon() {
		return 'dashicons-admin-generic';
	}

	/**
	 * A brief plain-English summary of what this action does given a config.
	 * Used by the live summary bar. Override for a richer description.
	 *
	 * @since  0.4.0
	 * @param  array $config Saved config values for this action instance.
	 * @return string
	 */
	public function get_summary( array $config ) {
		return $this->get_label();
	}

	/**
	 * Serialise to an array for wp_localize_script / JSON.
	 *
	 * @since  0.4.0
	 * @return array
	 */
	public function to_array() {
		return array(
			'type'        => $this->get_type(),
			'label'       => $this->get_label(),
			'description' => $this->get_description(),
			'icon'        => $this->get_icon(),
			'fields'      => $this->get_fields(),
		);
	}

	/**
	 * Execute the action.
	 *
	 * @since  0.3.0
	 * @param  array $config  Saved configuration for this action instance.
	 * @param  array $payload Trigger payload (already with placeholders resolved).
	 * @param  bool  $dry_run If true, simulate without side effects.
	 * @return FlowPress_Action_Result
	 */
	abstract public function execute( array $config, array $payload, bool $dry_run = false ): FlowPress_Action_Result;
}
