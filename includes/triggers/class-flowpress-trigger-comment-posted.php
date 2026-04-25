<?php
/**
 * Trigger: Comment Posted.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/triggers
 * @since      0.1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fires when a new comment is posted (any status).
 */
class FlowPress_Trigger_Comment_Posted extends FlowPress_Abstract_Trigger {

	public function get_type(): string {
		return 'comment_posted';
	}

	public function get_label(): string {
		return __( 'Comment Posted', 'flowpress' );
	}

	public function get_description(): string {
		return __( 'Fires when a visitor submits a new comment on any post.', 'flowpress' );
	}

	public function get_icon(): string {
		return 'dashicons-admin-comments';
	}

	public function get_tokens(): array {
		return array(
			array( 'token' => 'comment_id',           'label' => __( 'Comment ID', 'flowpress' ) ),
			array( 'token' => 'comment_author',        'label' => __( 'Comment Author Name', 'flowpress' ) ),
			array( 'token' => 'comment_author_email',  'label' => __( 'Comment Author Email', 'flowpress' ) ),
			array( 'token' => 'comment_content',       'label' => __( 'Comment Content', 'flowpress' ) ),
			array( 'token' => 'comment_status',        'label' => __( 'Comment Status', 'flowpress' ) ),
			array( 'token' => 'post_id',               'label' => __( 'Post ID', 'flowpress' ) ),
			array( 'token' => 'post_title',            'label' => __( 'Post Title', 'flowpress' ) ),
			array( 'token' => 'post_url',              'label' => __( 'Post URL', 'flowpress' ) ),
		);
	}

	public function get_sample_payload(): array {
		return array(
			'comment_id'          => '42',
			'comment_author'      => 'Jane Smith',
			'comment_author_email'=> 'jane@example.com',
			'comment_content'     => 'Great post!',
			'comment_status'      => '1',
			'post_id'             => '10',
			'post_title'          => 'Hello World',
			'post_url'            => home_url( '/?p=10' ),
		);
	}

	public function attach(): void {
		add_action( 'comment_post', array( $this, 'handle' ), 10, 3 );
	}

	/**
	 * @param int        $comment_id     Comment ID.
	 * @param int|string $comment_status Comment approved status (0, 1, or 'spam').
	 * @param array      $comment_data   Comment data array.
	 */
	public function handle( int $comment_id, $comment_status, array $comment_data ): void {
		$post = get_post( (int) $comment_data['comment_post_ID'] );

		FlowPress_Runner::run(
			$this->get_type(),
			array(
				'comment_id'           => (string) $comment_id,
				'comment_author'       => $comment_data['comment_author']       ?? '',
				'comment_author_email' => $comment_data['comment_author_email'] ?? '',
				'comment_content'      => $comment_data['comment_content']      ?? '',
				'comment_status'       => (string) $comment_status,
				'post_id'              => $post ? (string) $post->ID : '',
				'post_title'           => $post ? $post->post_title : '',
				'post_url'             => $post ? get_permalink( $post ) : '',
			)
		);
	}
}
