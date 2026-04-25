<?php
/**
 * Registry for all available FlowPress actions.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Action_Registry
 *
 * @since 0.3.0
 */
class FlowPress_Action_Registry {

	/** @var FlowPress_Abstract_Action[] */
	private static $actions = array();

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
		 * Register core actions.
		 *
		 * @since 0.3.0
		 * @param string $registry_class Pass to ::register().
		 */
		do_action( 'flowpress_register_actions', self::class );
	}

	/**
	 * Register an action.
	 *
	 * @since  0.3.0
	 * @param  FlowPress_Abstract_Action $action Action instance.
	 * @return void
	 */
	public static function register( FlowPress_Abstract_Action $action ) {
		self::$actions[ $action->get_type() ] = $action;
	}

	/**
	 * Retrieve an action by type slug.
	 *
	 * @since  0.3.0
	 * @param  string $type Action type slug.
	 * @return FlowPress_Abstract_Action|null
	 */
	public static function get( $type ) {
		return self::$actions[ $type ] ?? null;
	}

	/**
	 * Return all registered actions.
	 *
	 * @since  0.3.0
	 * @return FlowPress_Abstract_Action[]
	 */
	public static function all() {
		return self::$actions;
	}
}
