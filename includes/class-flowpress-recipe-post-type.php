<?php
/**
 * Registers the flowpress_recipe custom post type.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.2.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Recipe_Post_Type
 *
 * Registers and configures the `flowpress_recipe` CPT which stores all recipes.
 *
 * @since 0.2.0
 */
class FlowPress_Recipe_Post_Type {

	const POST_TYPE = 'flowpress_recipe';

	/**
	 * Register hooks.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_post_statuses' ) );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
	}

	/**
	 * Register the flowpress_recipe post type.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => __( 'Recipes', 'flowpress' ),
			'singular_name'         => __( 'Recipe', 'flowpress' ),
			'add_new'               => __( 'Add Recipe', 'flowpress' ),
			'add_new_item'          => __( 'Add New Recipe', 'flowpress' ),
			'edit_item'             => __( 'Edit Recipe', 'flowpress' ),
			'new_item'              => __( 'New Recipe', 'flowpress' ),
			'view_item'             => __( 'View Recipe', 'flowpress' ),
			'search_items'          => __( 'Search Recipes', 'flowpress' ),
			'not_found'             => __( 'No recipes found.', 'flowpress' ),
			'not_found_in_trash'    => __( 'No recipes found in Trash.', 'flowpress' ),
			'all_items'             => __( 'All Recipes', 'flowpress' ),
			'menu_name'             => __( 'FlowPress', 'flowpress' ),
			'name_admin_bar'        => __( 'Recipe', 'flowpress' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => false, // We manage our own admin screens.
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => false,
			// Use the default 'post' capability type without any overrides.
			// Custom capabilities mapped to manage_options with map_meta_cap=true
			// cause WP 6.1+ to treat manage_options as a meta cap requiring a post
			// ID, which breaks current_user_can('manage_options') site-wide.
			// Access to recipe data is already gated by manage_options checks in
			// every FlowPress admin callback.
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor', 'revisions', 'author' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register custom statuses: fp_enabled, fp_disabled (draft is built-in).
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function register_post_statuses() {
		register_post_status(
			'fp_enabled',
			array(
				'label'                     => _x( 'Enabled', 'post status', 'flowpress' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of recipes. */
				'label_count'               => _n_noop( 'Enabled <span class="count">(%s)</span>', 'Enabled <span class="count">(%s)</span>', 'flowpress' ),
			)
		);

		register_post_status(
			'fp_disabled',
			array(
				'label'                     => _x( 'Disabled', 'post status', 'flowpress' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of recipes. */
				'label_count'               => _n_noop( 'Disabled <span class="count">(%s)</span>', 'Disabled <span class="count">(%s)</span>', 'flowpress' ),
			)
		);
	}

	/**
	 * Customise the post updated admin notices.
	 *
	 * @since  0.2.0
	 * @param  array $messages Existing messages.
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		$messages[ self::POST_TYPE ] = array(
			0  => '',
			1  => __( 'Recipe updated.', 'flowpress' ),
			2  => __( 'Custom field updated.', 'flowpress' ),
			3  => __( 'Custom field deleted.', 'flowpress' ),
			4  => __( 'Recipe updated.', 'flowpress' ),
			5  => __( 'Recipe restored to revision.', 'flowpress' ),
			6  => __( 'Recipe saved.', 'flowpress' ),
			7  => __( 'Recipe saved.', 'flowpress' ),
			8  => __( 'Recipe submitted.', 'flowpress' ),
			9  => __( 'Recipe scheduled.', 'flowpress' ),
			10 => __( 'Recipe draft updated.', 'flowpress' ),
		);

		return $messages;
	}
}
