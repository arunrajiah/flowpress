<?php
/**
 * Recipe model — wraps a flowpress_recipe post with typed getters/setters.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.2.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Recipe
 *
 * Represents a single automation recipe. All persistence goes through this
 * class so that other code never touches WP_Post directly.
 *
 * @since 0.2.0
 */
class FlowPress_Recipe {

	const STATUS_DRAFT    = 'draft';
	const STATUS_ENABLED  = 'fp_enabled';
	const STATUS_DISABLED = 'fp_disabled';

	/**
	 * @var WP_Post
	 */
	private $post;

	/**
	 * @param WP_Post|int $post Post object or ID.
	 * @throws InvalidArgumentException If the post is not a flowpress_recipe.
	 */
	public function __construct( $post ) {
		if ( is_int( $post ) || is_string( $post ) ) {
			$post = get_post( (int) $post );
		}

		if ( ! $post instanceof WP_Post || FlowPress_Recipe_Post_Type::POST_TYPE !== $post->post_type ) {
			throw new InvalidArgumentException( 'Invalid recipe post.' );
		}

		$this->post = $post;
	}

	/** @return int */
	public function get_id() {
		return $this->post->ID;
	}

	/** @return string */
	public function get_title() {
		return $this->post->post_title;
	}

	/** @return string */
	public function get_description() {
		return $this->post->post_content;
	}

	/** @return string One of STATUS_* constants. */
	public function get_status() {
		return $this->post->post_status;
	}

	/** @return string Human-readable status label. */
	public function get_status_label() {
		$labels = array(
			self::STATUS_DRAFT    => __( 'Draft', 'flowpress' ),
			self::STATUS_ENABLED  => __( 'Enabled', 'flowpress' ),
			self::STATUS_DISABLED => __( 'Disabled', 'flowpress' ),
		);

		return $labels[ $this->post->post_status ] ?? $this->post->post_status;
	}

	/** @return bool */
	public function is_enabled() {
		return self::STATUS_ENABLED === $this->post->post_status;
	}

	/** @return bool */
	public function is_complete() {
		$trigger = get_post_meta( $this->post->ID, '_flowpress_trigger', true );
		$actions = get_post_meta( $this->post->ID, '_flowpress_actions', true );

		return ! empty( $trigger ) && ! empty( $actions );
	}

	/** @return string|null Trigger type slug, or null if not set. */
	public function get_trigger() {
		$trigger = get_post_meta( $this->post->ID, '_flowpress_trigger', true );
		return $trigger ?: null;
	}

	/** @return array Trigger configuration (e.g. slug for incoming webhook). */
	public function get_trigger_config() {
		$config = get_post_meta( $this->post->ID, '_fp_trigger_config', true );
		return is_array( $config ) ? $config : array();
	}

	/** @return array Action configurations. */
	public function get_actions() {
		$actions = get_post_meta( $this->post->ID, '_flowpress_actions', true );
		return is_array( $actions ) ? $actions : array();
	}

	/**
	 * Return the conditions block: { logic: 'AND'|'OR', items: [...] }.
	 *
	 * @since  0.5.0
	 * @return array
	 */
	public function get_conditions() {
		$conditions = get_post_meta( $this->post->ID, '_flowpress_conditions', true );
		if ( ! is_array( $conditions ) ) {
			return array( 'logic' => 'AND', 'items' => array() );
		}
		return $conditions;
	}

	/**
	 * Persist changes to the underlying WP_Post record.
	 *
	 * @param  array $data Keys: title, description, status, trigger, actions.
	 * @return true|WP_Error
	 */
	public function update( array $data ) {
		$post_data = array( 'ID' => $this->post->ID );

		if ( isset( $data['title'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $data['title'] );
		}

		if ( isset( $data['description'] ) ) {
			$post_data['post_content'] = wp_kses_post( $data['description'] );
		}

		if ( isset( $data['status'] ) && in_array( $data['status'], array( self::STATUS_DRAFT, self::STATUS_ENABLED, self::STATUS_DISABLED ), true ) ) {
			$post_data['post_status'] = $data['status'];
		}

		$result = wp_update_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( isset( $data['trigger'] ) ) {
			update_post_meta( $this->post->ID, '_flowpress_trigger', sanitize_key( $data['trigger'] ) );
		}

		if ( isset( $data['trigger_config'] ) && is_array( $data['trigger_config'] ) ) {
			update_post_meta( $this->post->ID, '_fp_trigger_config', $data['trigger_config'] );
		}

		if ( isset( $data['actions'] ) && is_array( $data['actions'] ) ) {
			update_post_meta( $this->post->ID, '_flowpress_actions', $data['actions'] );
		}

		if ( isset( $data['conditions'] ) && is_array( $data['conditions'] ) ) {
			update_post_meta( $this->post->ID, '_flowpress_conditions', $data['conditions'] );
		}

		// Reload post object to reflect changes.
		$this->post = get_post( $this->post->ID );

		return true;
	}

	/**
	 * Create a new recipe.
	 *
	 * @param  array $data Keys: title, description, trigger, trigger_config, actions, conditions.
	 * @return FlowPress_Recipe|WP_Error
	 */
	public static function create( array $data ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => FlowPress_Recipe_Post_Type::POST_TYPE,
				'post_title'   => sanitize_text_field( $data['title'] ?? __( 'Untitled Recipe', 'flowpress' ) ),
				'post_content' => wp_kses_post( $data['description'] ?? '' ),
				'post_status'  => self::STATUS_DRAFT,
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$recipe = new self( $post_id );

		// Save meta fields (trigger, actions, conditions, etc.) if provided.
		$meta_keys = array( 'trigger', 'trigger_config', 'actions', 'conditions' );
		$meta_data = array_intersect_key( $data, array_flip( $meta_keys ) );
		if ( ! empty( $meta_data ) ) {
			$recipe->update( $meta_data );
		}

		return $recipe;
	}

	/**
	 * Duplicate this recipe and return the new instance.
	 *
	 * @return FlowPress_Recipe|WP_Error
	 */
	public function duplicate() {
		$new_id = wp_insert_post(
			array(
				'post_type'    => FlowPress_Recipe_Post_Type::POST_TYPE,
				/* translators: %s: original recipe title. */
				'post_title'   => sprintf( __( '%s (copy)', 'flowpress' ), $this->get_title() ),
				'post_content' => $this->get_description(),
				'post_status'  => self::STATUS_DRAFT,
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $new_id ) ) {
			return $new_id;
		}

		// Copy meta.
		$trigger = $this->get_trigger();
		if ( $trigger ) {
			update_post_meta( $new_id, '_flowpress_trigger', $trigger );
		}

		$actions = $this->get_actions();
		if ( $actions ) {
			update_post_meta( $new_id, '_flowpress_actions', $actions );
		}

		return new self( $new_id );
	}

	/**
	 * Delete this recipe permanently.
	 *
	 * @return bool
	 */
	public function delete() {
		return (bool) wp_delete_post( $this->post->ID, true );
	}
}
