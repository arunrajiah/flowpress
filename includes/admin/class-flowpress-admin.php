<?php
/**
 * Admin-facing functionality bootstrap.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/admin
 * @since      0.2.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Admin
 *
 * Registers admin menus, enqueues assets, and wires up the recipe list and
 * edit screens.
 *
 * @since 0.2.0
 */
class FlowPress_Admin {

	/**
	 * Register hooks.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Handle form POSTs and list-page GET actions early — before any HTML output —
		// so wp_safe_redirect() works correctly.
		add_action( 'admin_init', array( $this, 'handle_early_post' ) );
		add_action( 'admin_init', array( $this, 'handle_early_list_action' ) );

		// Plugin row meta links (Plugins screen).
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

		// AJAX handlers for recipe actions.
		add_action( 'wp_ajax_flowpress_save_recipe', array( $this, 'ajax_save_recipe' ) );
		add_action( 'wp_ajax_flowpress_delete_recipe', array( $this, 'ajax_delete_recipe' ) );
		add_action( 'wp_ajax_flowpress_duplicate_recipe', array( $this, 'ajax_duplicate_recipe' ) );
		add_action( 'wp_ajax_flowpress_toggle_recipe_status', array( $this, 'ajax_toggle_recipe_status' ) );
		add_action( 'wp_ajax_flowpress_test_recipe', array( $this, 'ajax_test_recipe' ) );
	}

	/**
	 * Add GitHub and sponsor links to the FlowPress row on the Plugins screen.
	 *
	 * @since  0.6.0
	 * @param  string[] $links Existing meta links.
	 * @param  string   $file  Plugin basename.
	 * @return string[]
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( FLOWPRESS_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		$links[] = '<a href="https://github.com/arunrajiah" target="_blank" rel="noopener noreferrer">' . esc_html__( 'By arunrajiah', 'flowpress' ) . '</a>';
		$links[] = '<a href="https://github.com/sponsors/arunrajiah" target="_blank" rel="noopener noreferrer" style="color:#e25555;">&#9829; ' . esc_html__( 'Sponsor', 'flowpress' ) . '</a>';

		return $links;
	}

	/**
	 * Intercept recipe row/bulk actions on the list page on admin_init
	 * (before any HTML output) so that wp_safe_redirect() works correctly.
	 *
	 * @since  0.5.0
	 * @return void
	 */
	public function handle_early_list_action() {
		if ( 'GET' !== $_SERVER['REQUEST_METHOD'] && 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		if ( 'flowpress' !== $page ) {
			return;
		}

		$action    = isset( $_GET['fp_action'] ) ? sanitize_key( $_GET['fp_action'] ) : '';
		$recipe_id = isset( $_GET['recipe_id'] ) ? absint( $_GET['recipe_id'] ) : 0;

		if ( ! $action || ! $recipe_id ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'flowpress_recipe_action_' . $recipe_id );

		try {
			$recipe = new FlowPress_Recipe( $recipe_id );
		} catch ( InvalidArgumentException $e ) {
			return;
		}

		switch ( $action ) {
			case 'enable':
				$recipe->update( array( 'status' => FlowPress_Recipe::STATUS_ENABLED ) );
				FlowPress_Audit_Log::log( $recipe_id, 'status_changed', __( 'Recipe enabled.', 'flowpress' ) );
				wp_safe_redirect( add_query_arg( 'fp_notice', 'enabled', admin_url( 'admin.php?page=flowpress' ) ) );
				exit;

			case 'disable':
				$recipe->update( array( 'status' => FlowPress_Recipe::STATUS_DISABLED ) );
				FlowPress_Audit_Log::log( $recipe_id, 'status_changed', __( 'Recipe disabled.', 'flowpress' ) );
				wp_safe_redirect( add_query_arg( 'fp_notice', 'disabled', admin_url( 'admin.php?page=flowpress' ) ) );
				exit;

			case 'delete':
				FlowPress_Audit_Log::delete_for_recipe( $recipe_id );
				$recipe->delete();
				wp_safe_redirect( add_query_arg( 'fp_notice', 'deleted', admin_url( 'admin.php?page=flowpress' ) ) );
				exit;

			case 'duplicate':
				$new_recipe = $recipe->duplicate();
				if ( ! is_wp_error( $new_recipe ) ) {
					FlowPress_Audit_Log::log( $new_recipe->get_id(), 'created', sprintf( /* translators: %d: original recipe ID. */ __( 'Duplicated from recipe #%d.', 'flowpress' ), $recipe_id ) );
					wp_safe_redirect( add_query_arg( array( 'fp_notice' => 'duplicated', 'recipe_id' => $new_recipe->get_id() ), admin_url( 'admin.php?page=flowpress' ) ) );
					exit;
				}
				break;
		}
	}

	/**
	 * Intercept FlowPress form POSTs on admin_init (before any HTML output)
	 * so that wp_safe_redirect() works correctly.
	 *
	 * @since  0.5.0
	 * @return void
	 */
	public function handle_early_post() {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';

		if ( 'flowpress-new' === $page || 'flowpress-edit' === $page ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$recipe    = null;
			$recipe_id = isset( $_GET['recipe_id'] ) ? absint( $_GET['recipe_id'] ) : 0;
			if ( $recipe_id ) {
				try {
					$recipe = new FlowPress_Recipe( $recipe_id );
				} catch ( InvalidArgumentException $e ) {
					wp_die( esc_html__( 'Recipe not found.', 'flowpress' ) );
				}
			}

			$this->handle_save_recipe( $recipe );
		}
	}

	/**
	 * Register the top-level FlowPress admin menu.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function register_menus() {
		add_menu_page(
			__( 'FlowPress', 'flowpress' ),
			__( 'FlowPress', 'flowpress' ),
			'manage_options',
			'flowpress',
			array( $this, 'render_recipes_page' ),
			'dashicons-controls-repeat',
			58
		);

		add_submenu_page(
			'flowpress',
			__( 'Recipes', 'flowpress' ),
			__( 'Recipes', 'flowpress' ),
			'manage_options',
			'flowpress',
			array( $this, 'render_recipes_page' )
		);

		add_submenu_page(
			'flowpress',
			__( 'Add Recipe', 'flowpress' ),
			__( 'Add Recipe', 'flowpress' ),
			'manage_options',
			'flowpress-new',
			array( $this, 'render_edit_recipe_page' )
		);

		// Hidden submenu page for editing an existing recipe.
		add_submenu_page(
			null,
			__( 'Edit Recipe', 'flowpress' ),
			__( 'Edit Recipe', 'flowpress' ),
			'manage_options',
			'flowpress-edit',
			array( $this, 'render_edit_recipe_page' )
		);

		// Runs dashboard.
		add_submenu_page(
			'flowpress',
			__( 'Runs', 'flowpress' ),
			__( 'Runs', 'flowpress' ),
			'manage_options',
			'flowpress-runs',
			array( $this, 'render_runs_page' )
		);
	}

	/**
	 * Render the Runs dashboard page.
	 *
	 * @since  0.5.0
	 * @return void
	 */
	public function render_runs_page() {
		$runs_admin = new FlowPress_Runs_Admin();
		$runs_admin->render();
	}

	/**
	 * Enqueue admin CSS/JS on FlowPress screens only.
	 *
	 * @since  0.2.0
	 * @param  string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		$flowpress_hooks = array(
			'toplevel_page_flowpress',
			'flowpress_page_flowpress-new',
			'flowpress_page_flowpress-edit',
			'flowpress_page_flowpress-runs',
		);

		if ( ! in_array( $hook_suffix, $flowpress_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'flowpress-admin',
			FLOWPRESS_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			FLOWPRESS_VERSION
		);

		wp_enqueue_script(
			'flowpress-admin',
			FLOWPRESS_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			FLOWPRESS_VERSION,
			true
		);

		// Build trigger and action catalogues for the JS builder.
		$triggers_data = array();
		foreach ( FlowPress_Trigger_Registry::all() as $t ) {
			$triggers_data[] = $t->to_array();
		}

		$actions_data = array();
		foreach ( FlowPress_Action_Registry::all() as $a ) {
			$actions_data[] = $a->to_array();
		}

		wp_localize_script(
			'flowpress-admin',
			'flowpressAdmin',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'flowpress_admin' ),
				'triggers' => $triggers_data,
				'actions'  => $actions_data,
				'strings'  => array(
					'confirmDelete'       => __( 'Are you sure you want to delete this recipe? This cannot be undone.', 'flowpress' ),
					'saving'              => __( 'Saving…', 'flowpress' ),
					'saved'               => __( 'Recipe saved.', 'flowpress' ),
					'testRecipe'          => __( 'Test Recipe', 'flowpress' ),
					'testing'             => __( 'Testing…', 'flowpress' ),
					'rerunning'           => __( 'Re-running…', 'flowpress' ),
					'rerun'               => __( 'Re-run', 'flowpress' ),
					'error'               => __( 'Something went wrong. Please try again.', 'flowpress' ),
					'searchTriggers'      => __( 'Search triggers…', 'flowpress' ),
					'searchActions'       => __( 'Search actions…', 'flowpress' ),
					'noTriggersFound'     => __( 'No triggers match your search.', 'flowpress' ),
					'noActionsFound'      => __( 'No actions match your search.', 'flowpress' ),
					'addAction'           => __( '+ Add another action', 'flowpress' ),
					'removeAction'        => __( 'Remove', 'flowpress' ),
					'selectTriggerFirst'  => __( 'Select a trigger first to see available tokens.', 'flowpress' ),
					'insertToken'         => __( 'Insert token', 'flowpress' ),
					'chooseAction'        => __( 'Choose action type…', 'flowpress' ),
					'summaryPrefix'       => __( 'When', 'flowpress' ),
					'summaryConnector'    => __( 'and', 'flowpress' ),
					'summaryNoTrigger'    => __( 'Choose a trigger to see a summary of your recipe.', 'flowpress' ),
					'validationRequired'  => __( 'This field is required.', 'flowpress' ),
					'validationNoTrigger' => __( 'Please select a trigger.', 'flowpress' ),
					'validationNoAction'  => __( 'Please add at least one action.', 'flowpress' ),
				),
			)
		);
	}

	/**
	 * Render the Recipes list page.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function render_recipes_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'flowpress' ) );
		}

		require_once FLOWPRESS_PLUGIN_DIR . 'includes/admin/class-flowpress-recipes-list-table.php';
		$list_table = new FlowPress_Recipes_List_Table();
		$list_table->prepare_items();

		echo '<div class="wrap flowpress-wrap">';

		require_once FLOWPRESS_PLUGIN_DIR . 'includes/admin/views/promo-banner.php';

		echo '<h1 class="wp-heading-inline">' . esc_html__( 'FlowPress Recipes', 'flowpress' ) . '</h1>';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=flowpress-new' ) ) . '" class="page-title-action">' . esc_html__( 'Add Recipe', 'flowpress' ) . '</a>';

		$this->render_admin_notices();

		echo '<form method="post">';
		$list_table->display();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render inline admin notice from redirect parameter.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	private function render_admin_notices() {
		$notice = isset( $_GET['fp_notice'] ) ? sanitize_key( $_GET['fp_notice'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $notice ) {
			return;
		}

		$messages = array(
			'saved'      => array( 'success', __( 'Recipe saved.', 'flowpress' ) ),
			'enabled'    => array( 'success', __( 'Recipe enabled.', 'flowpress' ) ),
			'disabled'   => array( 'success', __( 'Recipe disabled.', 'flowpress' ) ),
			'deleted'    => array( 'success', __( 'Recipe deleted.', 'flowpress' ) ),
			'duplicated' => array( 'success', __( 'Recipe duplicated.', 'flowpress' ) ),
			'error'      => array( 'error', __( 'Something went wrong. Please try again.', 'flowpress' ) ),
		);

		if ( isset( $messages[ $notice ] ) ) {
			list( $type, $text ) = $messages[ $notice ];
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $type ),
				esc_html( $text )
			);
		}
	}

	/**
	 * Render the Add / Edit recipe page.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function render_edit_recipe_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'flowpress' ) );
		}

		$recipe_id = isset( $_GET['recipe_id'] ) ? absint( $_GET['recipe_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$recipe    = null;

		if ( $recipe_id ) {
			try {
				$recipe = new FlowPress_Recipe( $recipe_id );
			} catch ( InvalidArgumentException $e ) {
				wp_die( esc_html__( 'Recipe not found.', 'flowpress' ) );
			}
		}

		require_once FLOWPRESS_PLUGIN_DIR . 'includes/admin/views/builder.php';
	}

	/**
	 * Handle recipe save from the edit form.
	 *
	 * @since  0.2.0
	 * @param  FlowPress_Recipe|null $recipe Existing recipe or null for new.
	 * @return void
	 */
	private function handle_save_recipe( $recipe ) {
		check_admin_referer( 'flowpress_save_recipe' );

		// Build sanitised actions array from posted fp_actions[].
		$raw_actions    = isset( $_POST['fp_actions'] ) && is_array( $_POST['fp_actions'] ) ? wp_unslash( $_POST['fp_actions'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$sanitised_actions = array();
		foreach ( $raw_actions as $raw_action ) {
			$type = sanitize_key( $raw_action['type'] ?? '' );
			if ( ! $type ) {
				continue;
			}
			$cfg = array();
			if ( isset( $raw_action['config'] ) && is_array( $raw_action['config'] ) ) {
				foreach ( $raw_action['config'] as $cfg_key => $cfg_val ) {
					$cfg[ sanitize_key( $cfg_key ) ] = sanitize_textarea_field( $cfg_val );
				}
			}
			$sanitised_actions[] = array( 'type' => $type, 'config' => $cfg );
		}

		// Sanitise conditions block from posted fp_conditions.
		$raw_cond_logic = isset( $_POST['fp_conditions_logic'] ) ? sanitize_key( wp_unslash( $_POST['fp_conditions_logic'] ) ) : 'AND';
		$raw_cond_items = isset( $_POST['fp_conditions'] ) && is_array( $_POST['fp_conditions'] ) ? wp_unslash( $_POST['fp_conditions'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$sanitised_conditions = array(
			'logic' => strtoupper( $raw_cond_logic ) === 'OR' ? 'OR' : 'AND',
			'items' => array(),
		);
		foreach ( $raw_cond_items as $raw_item ) {
			$field    = sanitize_key( $raw_item['field'] ?? '' );
			$operator = sanitize_key( $raw_item['operator'] ?? 'equals' );
			$value    = sanitize_text_field( $raw_item['value'] ?? '' );
			if ( $field && $operator ) {
				$sanitised_conditions['items'][] = array( 'field' => $field, 'operator' => $operator, 'value' => $value );
			}
		}

		// Trigger config (e.g. slug for incoming_webhook trigger).
		$trigger_type   = isset( $_POST['fp_trigger'] ) ? sanitize_key( wp_unslash( $_POST['fp_trigger'] ) ) : '';
		$trigger_config = array();
		if ( 'incoming_webhook' === $trigger_type && isset( $_POST['fp_trigger_config_slug'] ) ) {
			$trigger_config['slug'] = sanitize_title( wp_unslash( $_POST['fp_trigger_config_slug'] ) );
		}

		$data = array(
			'title'          => isset( $_POST['fp_title'] ) ? sanitize_text_field( wp_unslash( $_POST['fp_title'] ) ) : '',
			'description'    => isset( $_POST['fp_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fp_description'] ) ) : '',
			'trigger'        => $trigger_type,
			'trigger_config' => $trigger_config,
			'actions'        => $sanitised_actions,
			'conditions'     => $sanitised_conditions,
		);

		if ( $recipe ) {
			$result  = $recipe->update( $data );
			$action  = 'updated';
			$summary = __( 'Recipe details updated.', 'flowpress' );
		} else {
			$result = FlowPress_Recipe::create( $data );
			if ( ! is_wp_error( $result ) ) {
				$recipe = $result;
			}
			$action  = 'created';
			$summary = __( 'Recipe created.', 'flowpress' );
		}

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'fp_notice', 'error', wp_get_referer() ) );
			exit;
		}

		FlowPress_Audit_Log::log( $recipe->get_id(), $action, $summary );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => 'flowpress-edit',
					'recipe_id' => $recipe->get_id(),
					'fp_notice' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// -------------------------------------------------------------------------
	// AJAX handlers
	// -------------------------------------------------------------------------

	/**
	 * AJAX: save a recipe (used by the JS edit form).
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function ajax_save_recipe() {
		check_ajax_referer( 'flowpress_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'flowpress' ) ), 403 );
		}

		$recipe_id = isset( $_POST['recipe_id'] ) ? absint( $_POST['recipe_id'] ) : 0;
		$data      = array(
			'title'       => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
			'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
		);

		if ( $recipe_id ) {
			try {
				$recipe = new FlowPress_Recipe( $recipe_id );
			} catch ( InvalidArgumentException $e ) {
				wp_send_json_error( array( 'message' => __( 'Recipe not found.', 'flowpress' ) ), 404 );
			}

			$result = $recipe->update( $data );
		} else {
			$result = FlowPress_Recipe::create( $data );
			if ( ! is_wp_error( $result ) ) {
				$recipe = $result;
			}
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		FlowPress_Audit_Log::log( $recipe->get_id(), $recipe_id ? 'updated' : 'created', __( 'Recipe saved via editor.', 'flowpress' ) );

		wp_send_json_success(
			array(
				'recipe_id' => $recipe->get_id(),
				'message'   => __( 'Recipe saved.', 'flowpress' ),
			)
		);
	}

	/**
	 * AJAX: delete a recipe.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function ajax_delete_recipe() {
		check_ajax_referer( 'flowpress_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'flowpress' ) ), 403 );
		}

		$recipe_id = isset( $_POST['recipe_id'] ) ? absint( $_POST['recipe_id'] ) : 0;

		try {
			$recipe = new FlowPress_Recipe( $recipe_id );
		} catch ( InvalidArgumentException $e ) {
			wp_send_json_error( array( 'message' => __( 'Recipe not found.', 'flowpress' ) ), 404 );
		}

		FlowPress_Audit_Log::delete_for_recipe( $recipe_id );
		$recipe->delete();

		wp_send_json_success( array( 'message' => __( 'Recipe deleted.', 'flowpress' ) ) );
	}

	/**
	 * AJAX: duplicate a recipe.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function ajax_duplicate_recipe() {
		check_ajax_referer( 'flowpress_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'flowpress' ) ), 403 );
		}

		$recipe_id = isset( $_POST['recipe_id'] ) ? absint( $_POST['recipe_id'] ) : 0;

		try {
			$recipe = new FlowPress_Recipe( $recipe_id );
		} catch ( InvalidArgumentException $e ) {
			wp_send_json_error( array( 'message' => __( 'Recipe not found.', 'flowpress' ) ), 404 );
		}

		$new_recipe = $recipe->duplicate();

		if ( is_wp_error( $new_recipe ) ) {
			wp_send_json_error( array( 'message' => $new_recipe->get_error_message() ) );
		}

		FlowPress_Audit_Log::log(
			$new_recipe->get_id(),
			'created',
			sprintf( /* translators: %d: original recipe ID. */ __( 'Duplicated from recipe #%d.', 'flowpress' ), $recipe_id )
		);

		wp_send_json_success(
			array(
				'recipe_id' => $new_recipe->get_id(),
				'message'   => __( 'Recipe duplicated.', 'flowpress' ),
				'edit_url'  => admin_url( 'admin.php?page=flowpress-edit&recipe_id=' . $new_recipe->get_id() ),
			)
		);
	}

	/**
	 * AJAX: toggle recipe enabled / disabled.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function ajax_toggle_recipe_status() {
		check_ajax_referer( 'flowpress_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'flowpress' ) ), 403 );
		}

		$recipe_id = isset( $_POST['recipe_id'] ) ? absint( $_POST['recipe_id'] ) : 0;

		try {
			$recipe = new FlowPress_Recipe( $recipe_id );
		} catch ( InvalidArgumentException $e ) {
			wp_send_json_error( array( 'message' => __( 'Recipe not found.', 'flowpress' ) ), 404 );
		}

		$new_status = $recipe->is_enabled() ? FlowPress_Recipe::STATUS_DISABLED : FlowPress_Recipe::STATUS_ENABLED;
		$recipe->update( array( 'status' => $new_status ) );

		FlowPress_Audit_Log::log(
			$recipe_id,
			'status_changed',
			FlowPress_Recipe::STATUS_ENABLED === $new_status ? __( 'Recipe enabled.', 'flowpress' ) : __( 'Recipe disabled.', 'flowpress' )
		);

		wp_send_json_success(
			array(
				'status'  => $new_status,
				'label'   => $recipe->get_status_label(),
				'message' => FlowPress_Recipe::STATUS_ENABLED === $new_status
					? __( 'Recipe enabled.', 'flowpress' )
					: __( 'Recipe disabled.', 'flowpress' ),
			)
		);
	}

	/**
	 * AJAX: test-run a recipe with sample data (dry run, no side effects).
	 *
	 * @since  0.3.0
	 * @return void
	 */
	public function ajax_test_recipe() {
		check_ajax_referer( 'flowpress_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'flowpress' ) ), 403 );
		}

		$recipe_id = isset( $_POST['recipe_id'] ) ? absint( $_POST['recipe_id'] ) : 0;

		try {
			$recipe = new FlowPress_Recipe( $recipe_id );
		} catch ( InvalidArgumentException $e ) {
			wp_send_json_error( array( 'message' => __( 'Recipe not found.', 'flowpress' ) ), 404 );
		}

		if ( ! $recipe->get_trigger() ) {
			wp_send_json_error( array( 'message' => __( 'This recipe has no trigger configured.', 'flowpress' ) ) );
		}

		if ( empty( $recipe->get_actions() ) ) {
			wp_send_json_error( array( 'message' => __( 'This recipe has no actions configured.', 'flowpress' ) ) );
		}

		$trigger = FlowPress_Trigger_Registry::get( $recipe->get_trigger() );
		if ( ! $trigger ) {
			wp_send_json_error( array( 'message' => __( 'Trigger type not found.', 'flowpress' ) ) );
		}

		$payload        = $trigger->get_sample_payload();
		$action_results = FlowPress_Runner::execute_recipe( $recipe, $recipe->get_trigger(), $payload, true );

		wp_send_json_success(
			array(
				'message'        => __( 'Test run complete. No real emails were sent.', 'flowpress' ),
				'payload'        => $payload,
				'action_results' => $action_results,
			)
		);
	}
}
