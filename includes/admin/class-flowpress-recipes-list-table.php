<?php
/**
 * WP_List_Table subclass for the recipes admin screen.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/admin
 * @since      0.2.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class FlowPress_Recipes_List_Table
 *
 * Renders the sortable, filterable recipes list table.
 *
 * @since 0.2.0
 */
class FlowPress_Recipes_List_Table extends WP_List_Table {

	/**
	 * @since 0.2.0
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'recipe',
				'plural'   => 'recipes',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Retrieve recipes from the database.
	 *
	 * @since  0.2.0
	 * @param  int    $per_page    Recipes per page.
	 * @param  int    $current_page Current page number.
	 * @param  string $status      Optional status filter.
	 * @return WP_Post[]
	 */
	private function get_recipes( $per_page = 20, $current_page = 1, $status = '' ) {
		$args = array(
			'post_type'      => FlowPress_Recipe_Post_Type::POST_TYPE,
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
			'orderby'        => isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'date', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'order'          => isset( $_GET['order'] ) && 'asc' === strtolower( sanitize_key( $_GET['order'] ) ) ? 'ASC' : 'DESC', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);

		if ( $status ) {
			$args['post_status'] = $status;
		} else {
			$args['post_status'] = array(
				FlowPress_Recipe::STATUS_DRAFT,
				FlowPress_Recipe::STATUS_ENABLED,
				FlowPress_Recipe::STATUS_DISABLED,
			);
		}

		return get_posts( $args );
	}

	/**
	 * Count recipes by status.
	 *
	 * @since  0.2.0
	 * @param  string $status Post status.
	 * @return int
	 */
	private function count_recipes( $status = '' ) {
		$args = array(
			'post_type'      => FlowPress_Recipe_Post_Type::POST_TYPE,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		if ( $status ) {
			$args['post_status'] = $status;
		} else {
			$args['post_status'] = array(
				FlowPress_Recipe::STATUS_DRAFT,
				FlowPress_Recipe::STATUS_ENABLED,
				FlowPress_Recipe::STATUS_DISABLED,
			);
		}

		$query = new WP_Query( $args );
		return $query->found_posts;
	}

	/**
	 * @inheritDoc
	 */
	public function prepare_items() {
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$status       = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Required: tell WP_List_Table which columns to render.
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->set_pagination_args(
			array(
				'total_items' => $this->count_recipes( $status ),
				'per_page'    => $per_page,
			)
		);

		$this->items = $this->get_recipes( $per_page, $current_page, $status );
	}

	/**
	 * @inheritDoc
	 */
	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />',
			'title'       => __( 'Recipe', 'flowpress' ),
			'description' => __( 'Description', 'flowpress' ),
			'status'      => __( 'Status', 'flowpress' ),
			'completeness'=> __( 'Completeness', 'flowpress' ),
			'author'      => __( 'Author', 'flowpress' ),
			'date'        => __( 'Last Modified', 'flowpress' ),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function get_sortable_columns() {
		return array(
			'title'  => array( 'title', false ),
			'status' => array( 'post_status', false ),
			'date'   => array( 'date', true ),
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function column_cb( $item ) {
		return '<input type="checkbox" name="recipe_ids[]" value="' . absint( $item->ID ) . '" />';
	}

	/**
	 * Column: title with row actions.
	 *
	 * @since  0.2.0
	 * @param  WP_Post $item Current post.
	 * @return string
	 */
	protected function column_title( $item ) {
		$edit_url = admin_url( 'admin.php?page=flowpress-edit&recipe_id=' . $item->ID );
		$title    = '<strong><a href="' . esc_url( $edit_url ) . '">' . esc_html( $item->post_title ?: __( '(no title)', 'flowpress' ) ) . '</a></strong>';

		$nonce = wp_create_nonce( 'flowpress_recipe_action_' . $item->ID );

		$enable_url    = add_query_arg( array( 'page' => 'flowpress', 'fp_action' => 'enable', 'recipe_id' => $item->ID, '_wpnonce' => $nonce ), admin_url( 'admin.php' ) );
		$disable_url   = add_query_arg( array( 'page' => 'flowpress', 'fp_action' => 'disable', 'recipe_id' => $item->ID, '_wpnonce' => $nonce ), admin_url( 'admin.php' ) );
		$duplicate_url = add_query_arg( array( 'page' => 'flowpress', 'fp_action' => 'duplicate', 'recipe_id' => $item->ID, '_wpnonce' => $nonce ), admin_url( 'admin.php' ) );
		$delete_url    = add_query_arg( array( 'page' => 'flowpress', 'fp_action' => 'delete', 'recipe_id' => $item->ID, '_wpnonce' => $nonce ), admin_url( 'admin.php' ) );

		$actions = array(
			'edit'      => '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'flowpress' ) . '</a>',
			'duplicate' => '<a href="' . esc_url( $duplicate_url ) . '">' . esc_html__( 'Duplicate', 'flowpress' ) . '</a>',
			'delete'    => '<a href="' . esc_url( $delete_url ) . '" class="fp-delete-link">' . esc_html__( 'Delete', 'flowpress' ) . '</a>',
		);

		if ( FlowPress_Recipe::STATUS_ENABLED === $item->post_status ) {
			$actions['disable'] = '<a href="' . esc_url( $disable_url ) . '">' . esc_html__( 'Disable', 'flowpress' ) . '</a>';
		} else {
			$actions['enable'] = '<a href="' . esc_url( $enable_url ) . '">' . esc_html__( 'Enable', 'flowpress' ) . '</a>';
		}

		return $title . $this->row_actions( $actions );
	}

	/**
	 * Column: description.
	 *
	 * @since  0.2.0
	 * @param  WP_Post $item Current post.
	 * @return string
	 */
	protected function column_description( $item ) {
		return esc_html( wp_trim_words( $item->post_content, 12, '&hellip;' ) );
	}

	/**
	 * Column: status badge.
	 *
	 * @since  0.2.0
	 * @param  WP_Post $item Current post.
	 * @return string
	 */
	protected function column_status( $item ) {
		$status_classes = array(
			FlowPress_Recipe::STATUS_ENABLED  => 'fp-status fp-status--enabled',
			FlowPress_Recipe::STATUS_DISABLED => 'fp-status fp-status--disabled',
			FlowPress_Recipe::STATUS_DRAFT    => 'fp-status fp-status--draft',
		);

		$labels = array(
			FlowPress_Recipe::STATUS_ENABLED  => __( 'Enabled', 'flowpress' ),
			FlowPress_Recipe::STATUS_DISABLED => __( 'Disabled', 'flowpress' ),
			FlowPress_Recipe::STATUS_DRAFT    => __( 'Draft', 'flowpress' ),
		);

		$class = $status_classes[ $item->post_status ] ?? 'fp-status';
		$label = $labels[ $item->post_status ] ?? esc_html( $item->post_status );

		return '<span class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Column: completeness indicator.
	 *
	 * @since  0.2.0
	 * @param  WP_Post $item Current post.
	 * @return string
	 */
	protected function column_completeness( $item ) {
		try {
			$recipe = new FlowPress_Recipe( $item );
		} catch ( InvalidArgumentException $e ) {
			return '&mdash;';
		}

		if ( $recipe->is_complete() ) {
			return '<span class="fp-complete dashicons dashicons-yes-alt" title="' . esc_attr__( 'Complete', 'flowpress' ) . '"></span>';
		}

		return '<span class="fp-incomplete dashicons dashicons-warning" title="' . esc_attr__( 'Incomplete — no trigger or actions set', 'flowpress' ) . '"></span>';
	}

	/**
	 * Column: author display name.
	 *
	 * @since  0.2.0
	 * @param  WP_Post $item Current post.
	 * @return string
	 */
	protected function column_author( $item ) {
		$user = get_user_by( 'id', $item->post_author );
		return $user ? esc_html( $user->display_name ) : '&mdash;';
	}

	/**
	 * Column: last modified date.
	 *
	 * @since  0.2.0
	 * @param  WP_Post $item Current post.
	 * @return string
	 */
	protected function column_date( $item ) {
		return esc_html(
			get_the_modified_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item )
		);
	}

	/**
	 * Fallback for unmapped columns.
	 *
	 * @since  0.2.0
	 * @param  WP_Post $item        Current post.
	 * @param  string  $column_name Column slug.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		return '&mdash;';
	}

	/**
	 * Views (status filter links) shown above the table.
	 *
	 * @since  0.2.0
	 * @return array
	 */
	protected function get_views() {
		$current_status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$base_url       = admin_url( 'admin.php?page=flowpress' );

		$statuses = array(
			''            => __( 'All', 'flowpress' ),
			'fp_enabled'  => __( 'Enabled', 'flowpress' ),
			'fp_disabled' => __( 'Disabled', 'flowpress' ),
			'draft'       => __( 'Draft', 'flowpress' ),
		);

		$views = array();
		foreach ( $statuses as $status => $label ) {
			$count   = $this->count_recipes( $status );
			$url     = $status ? add_query_arg( 'status', $status, $base_url ) : $base_url;
			$current = ( $status === $current_status ) ? ' class="current"' : '';
			$views[ $status ?: 'all' ] = '<a href="' . esc_url( $url ) . '"' . $current . '>' . esc_html( $label ) . ' <span class="count">(' . absint( $count ) . ')</span></a>';
		}

		return $views;
	}

	/**
	 * Bulk actions available on the list table.
	 *
	 * @since  0.2.0
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'bulk_enable'  => __( 'Enable', 'flowpress' ),
			'bulk_disable' => __( 'Disable', 'flowpress' ),
			'bulk_delete'  => __( 'Delete', 'flowpress' ),
		);
	}

	/**
	 * Text shown when no recipes exist.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No recipes found. Add your first recipe to get started.', 'flowpress' );
	}
}
