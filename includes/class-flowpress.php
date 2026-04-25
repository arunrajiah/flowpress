<?php
/**
 * The core plugin class.
 *
 * This is the central class that ties everything together. It is responsible
 * for loading all dependencies, setting the locale, defining the admin and
 * public-facing hooks, and running the plugin.
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
 * Class FlowPress
 *
 * Core plugin orchestrator. Implements the singleton pattern so that only one
 * instance is ever instantiated during a WordPress request lifecycle.
 *
 * @since 0.1.0
 */
class FlowPress {

	/**
	 * The single shared instance of this class.
	 *
	 * @since  0.1.0
	 * @access private
	 * @var    FlowPress|null $instance
	 */
	private static $instance = null;

	/**
	 * The plugin version string.
	 *
	 * @since  0.1.0
	 * @access protected
	 * @var    string $version
	 */
	protected $version;

	/**
	 * The plugin text domain used for internationalisation.
	 *
	 * @since  0.1.0
	 * @access protected
	 * @var    string $plugin_name
	 */
	protected $plugin_name;

	/**
	 * Private constructor — use {@see FlowPress::get_instance()} instead.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		$this->version     = FLOWPRESS_VERSION;
		$this->plugin_name = 'flowpress';
	}

	/**
	 * Retrieve (and lazily create) the singleton instance.
	 *
	 * @since  0.1.0
	 * @return FlowPress The single shared instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Prevent cloning of the singleton.
	 *
	 * @since  0.1.0
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent unserialising of the singleton.
	 *
	 * @since  0.1.0
	 * @throws \Exception If an attempt is made to unserialise.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialise a singleton.' );
	}

	/**
	 * Bootstrap the plugin: load dependencies, set locale, and register hooks.
	 *
	 * Called once from the main plugin file after activation hooks have been
	 * registered.
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function run() {
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load all required dependency files.
	 *
	 * The i18n class is always required. Additional classes (admin, public,
	 * etc.) will be added here as each phase is developed.
	 *
	 * @since  0.1.0
	 * @access private
	 * @return void
	 */
	private function load_dependencies() {
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-i18n.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-audit-log.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-recipe-post-type.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-recipe.php';

		// Phase 3: engine.
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-placeholder.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-action-result.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-abstract-trigger.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-abstract-action.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-trigger-registry.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-action-registry.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-run-log.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-runner.php';

		// Phase 5: conditions + reliability.
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-condition-evaluator.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-retry-queue.php';

		// Phase 6: incoming webhook REST handler.
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/class-flowpress-incoming-webhook.php';

		// Core triggers.
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/triggers/class-flowpress-trigger-post-published.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/triggers/class-flowpress-trigger-comment-posted.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/triggers/class-flowpress-trigger-user-registered.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/triggers/class-flowpress-trigger-user-role-changed.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/triggers/class-flowpress-trigger-woo-order-placed.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/triggers/class-flowpress-trigger-incoming-webhook.php';

		// Core actions.
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/actions/class-flowpress-action-send-email.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/actions/class-flowpress-action-outbound-webhook.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/actions/class-flowpress-action-woo-create-coupon.php';
		require_once FLOWPRESS_PLUGIN_DIR . 'includes/actions/class-flowpress-action-woo-order-note.php';

		if ( is_admin() ) {
			require_once FLOWPRESS_PLUGIN_DIR . 'includes/admin/class-flowpress-admin.php';
			require_once FLOWPRESS_PLUGIN_DIR . 'includes/admin/class-flowpress-recipes-list-table.php';
			require_once FLOWPRESS_PLUGIN_DIR . 'includes/admin/class-flowpress-runs-admin.php';
		}
	}

	/**
	 * Define the locale for internationalisation and load the text domain.
	 *
	 * @since  0.1.0
	 * @access private
	 * @return void
	 */
	private function set_locale() {
		$plugin_i18n = new FlowPress_i18n();
		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Register all admin-facing hooks and filters.
	 *
	 * Stub implementation for Phase 1. Admin menus, settings pages, and
	 * column additions will be wired up here in subsequent phases.
	 *
	 * @since  0.1.0
	 * @access private
	 * @return void
	 */
	private function define_admin_hooks() {
		$cpt = new FlowPress_Recipe_Post_Type();
		$cpt->register();

		// Register core triggers via hook so third parties can add theirs.
		add_action(
			'flowpress_register_triggers',
			static function ( $registry_class ) {
				$registry_class::register( new FlowPress_Trigger_Post_Published() );
				$registry_class::register( new FlowPress_Trigger_Comment_Posted() );
				$registry_class::register( new FlowPress_Trigger_User_Registered() );
				$registry_class::register( new FlowPress_Trigger_User_Role_Changed() );
				$registry_class::register( new FlowPress_Trigger_Woo_Order_Placed() );
				$registry_class::register( new FlowPress_Trigger_Incoming_Webhook() );
			}
		);

		// Register core actions via hook so third parties can add theirs.
		add_action(
			'flowpress_register_actions',
			static function ( $registry_class ) {
				$registry_class::register( new FlowPress_Action_Send_Email() );
				$registry_class::register( new FlowPress_Action_Outbound_Webhook() );
				if ( class_exists( 'WooCommerce' ) ) {
					$registry_class::register( new FlowPress_Action_Woo_Create_Coupon() );
					$registry_class::register( new FlowPress_Action_Woo_Order_Note() );
				}
			}
		);

		// Incoming webhook REST route.
		add_action( 'rest_api_init', array( 'FlowPress_Incoming_Webhook', 'register_routes' ) );

		// Boot registries after all plugins have had a chance to hook in.
		add_action( 'init', array( 'FlowPress_Trigger_Registry', 'init' ), 20 );
		add_action( 'init', array( 'FlowPress_Action_Registry', 'init' ), 20 );

		// WP-Cron hook for retry queue.
		add_action( FlowPress_Retry_Queue::CRON_HOOK, array( 'FlowPress_Retry_Queue', 'process' ) );

		if ( is_admin() ) {
			$admin = new FlowPress_Admin();
			$admin->register();

			$runs_admin = new FlowPress_Runs_Admin();
			$runs_admin->register();
		}
	}

	/**
	 * Return the plugin slug / text domain.
	 *
	 * @since  0.1.0
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Return the plugin version.
	 *
	 * @since  0.1.0
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
}
