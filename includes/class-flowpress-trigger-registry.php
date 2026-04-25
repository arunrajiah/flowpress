<?php
/**
 * Registry for all available FlowPress triggers.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Trigger_Registry
 *
 * A simple key→object map of registered triggers. Third-party plugins can
 * add their own triggers via the `flowpress_register_triggers` action.
 *
 * @since 0.3.0
 */
class FlowPress_Trigger_Registry {

	/** @var FlowPress_Abstract_Trigger[] */
	private static $triggers = array();

	/** @var bool */
	private static $initialized = false;

	/**
	 * Initialize the registry and fire the registration hook once.
	 *
	 * @since  0.3.0
	 * @return void
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		/**
		 * Register core triggers.
		 *
		 * @since 0.3.0
		 * @param FlowPress_Trigger_Registry $registry Pass to ::register().
		 */
		do_action( 'flowpress_register_triggers', self::class );
	}

	/**
	 * Register a trigger.
	 *
	 * @since  0.3.0
	 * @param  FlowPress_Abstract_Trigger $trigger Trigger instance.
	 * @return void
	 */
	public static function register( FlowPress_Abstract_Trigger $trigger ) {
		self::$triggers[ $trigger->get_type() ] = $trigger;
		$trigger->attach();
	}

	/**
	 * Retrieve a trigger by type slug.
	 *
	 * @since  0.3.0
	 * @param  string $type Trigger type slug.
	 * @return FlowPress_Abstract_Trigger|null
	 */
	public static function get( $type ) {
		return self::$triggers[ $type ] ?? null;
	}

	/**
	 * Return all registered triggers.
	 *
	 * @since  0.3.0
	 * @return FlowPress_Abstract_Trigger[]
	 */
	public static function all() {
		return self::$triggers;
	}
}
