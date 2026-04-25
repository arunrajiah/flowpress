<?php
/**
 * Trigger: a post is published.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/triggers
 * @since      0.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Trigger_Post_Published
 *
 * Fires when any post transitions to the "publish" status.
 * Emits: post_id, post_title, post_url, post_excerpt, post_author,
 *        post_author_email, post_date, site_name, site_url.
 *
 * @since 0.3.0
 */
class FlowPress_Trigger_Post_Published extends FlowPress_Abstract_Trigger {

	/** @inheritDoc */
	public function get_type() {
		return 'post_published';
	}

	/** @inheritDoc */
	public function get_icon() {
		return 'dashicons-edit-page';
	}

	/** @inheritDoc */
	public function get_label() {
		return __( 'A post is published', 'flowpress' );
	}

	/** @inheritDoc */
	public function get_description() {
		return __( 'Fires whenever any post type transitions to the Published status.', 'flowpress' );
	}

	/** @inheritDoc */
	public function get_tokens(): array {
		return array(
			array( 'token' => 'post_id',             'label' => __( 'Post ID', 'flowpress' ) ),
			array( 'token' => 'post_title',          'label' => __( 'Post title', 'flowpress' ) ),
			array( 'token' => 'post_url',            'label' => __( 'Post URL', 'flowpress' ) ),
			array( 'token' => 'post_excerpt',        'label' => __( 'Post excerpt', 'flowpress' ) ),
			array( 'token' => 'post_author',         'label' => __( 'Post author display name', 'flowpress' ) ),
			array( 'token' => 'post_author_email',   'label' => __( 'Post author email', 'flowpress' ) ),
			array( 'token' => 'post_date',           'label' => __( 'Post publish date', 'flowpress' ) ),
			array( 'token' => 'site_name',           'label' => __( 'Site name', 'flowpress' ) ),
			array( 'token' => 'site_url',            'label' => __( 'Site URL', 'flowpress' ) ),
			array( 'token' => 'post_type',           'label' => __( 'Post type slug', 'flowpress' ) ),
			array( 'token' => 'post_category_slugs', 'label' => __( 'Comma-separated category slugs', 'flowpress' ) ),
		);
	}

	/** @inheritDoc */
	public function get_sample_payload() {
		return array(
			'post_id'             => 42,
			'post_title'          => __( 'Hello World', 'flowpress' ),
			'post_url'            => home_url( '/hello-world/' ),
			'post_excerpt'        => __( 'This is a sample post excerpt.', 'flowpress' ),
			'post_author'         => __( 'Jane Doe', 'flowpress' ),
			'post_author_email'   => 'jane@example.com',
			'post_date'           => current_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'site_name'           => get_bloginfo( 'name' ),
			'site_url'            => home_url(),
			'post_type'           => 'post',
			'post_category_slugs' => 'news,featured',
		);
	}

	/** @inheritDoc */
	public function attach() {
		add_action( 'transition_post_status', array( $this, 'handle' ), 10, 3 );
	}

	/**
	 * Called by WordPress when any post changes status.
	 *
	 * @since  0.3.0
	 * @param  string  $new_status New post status.
	 * @param  string  $old_status Old post status.
	 * @param  WP_Post $post       The post object.
	 * @return void
	 */
	public function handle( $new_status, $old_status, $post ) {
		// Only fire when a post actually becomes published (not on re-saves of already-published posts).
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		// Ignore auto-drafts and revisions.
		if ( 'revision' === $post->post_type || 'auto-draft' === $post->post_status ) {
			return;
		}

		$author     = get_user_by( 'id', $post->post_author );
		$categories = get_the_category( $post->ID );
		$cat_slugs  = implode( ',', wp_list_pluck( $categories ?: array(), 'slug' ) );

		$payload = array(
			'post_id'             => $post->ID,
			'post_title'          => $post->post_title,
			'post_url'            => get_permalink( $post->ID ),
			'post_excerpt'        => $post->post_excerpt ?: wp_trim_words( $post->post_content, 30 ),
			'post_author'         => $author ? $author->display_name : '',
			'post_author_email'   => $author ? $author->user_email : '',
			'post_date'           => get_the_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $post ),
			'site_name'           => get_bloginfo( 'name' ),
			'site_url'            => home_url(),
			'post_type'           => $post->post_type,
			'post_category_slugs' => $cat_slugs,
		);

		FlowPress_Runner::run( $this->get_type(), $payload );
	}
}
