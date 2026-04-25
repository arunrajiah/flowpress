<?php
/**
 * Incoming webhook REST handler.
 *
 * Registers POST /wp-json/flowpress/v1/webhook/{slug} and dispatches to
 * FlowPress_Runner for any enabled recipe whose trigger type is
 * 'incoming_webhook' and whose slug config matches.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Incoming_Webhook
 */
class FlowPress_Incoming_Webhook {

	/** REST namespace. */
	const NAMESPACE = 'flowpress/v1';

	/** REST route. */
	const ROUTE = '/webhook/(?P<slug>[a-z0-9\-]+)';

	/**
	 * Register WP REST API route.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( static::class, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'slug' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_title',
					),
				),
			)
		);
	}

	/**
	 * Handle an incoming webhook request.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response
	 */
	public static function handle( WP_REST_Request $request ): WP_REST_Response {
		$slug = $request->get_param( 'slug' );

		// Decode JSON body if present.
		$body_raw  = $request->get_body();
		$body_data = array();

		if ( ! empty( $body_raw ) ) {
			$decoded = json_decode( $body_raw, true );
			if ( is_array( $decoded ) ) {
				$body_data = $decoded;
			}
		}

		$payload = array(
			'webhook_slug' => $slug,
			'payload_raw'  => $body_raw,
			'remote_ip'    => self::get_remote_ip(),
		);

		// Merge flat string values from the decoded body so they are token-accessible.
		foreach ( $body_data as $key => $value ) {
			if ( is_scalar( $value ) && ! isset( $payload[ $key ] ) ) {
				$payload[ 'body_' . sanitize_key( $key ) ] = (string) $value;
			}
		}

		// Find enabled recipes whose trigger slug matches.
		$matched = self::find_recipes_by_slug( $slug );

		if ( empty( $matched ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No matching webhook recipe found.', 'flowpress' ),
				),
				404
			);
		}

		$results = array();
		foreach ( $matched as $recipe ) {
			$action_results = FlowPress_Runner::execute_recipe( $recipe, 'incoming_webhook', $payload );
			$results[]      = array(
				'recipe_id' => $recipe->get_id(),
				'actions'   => count( $action_results ),
			);
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'message'  => __( 'Webhook received.', 'flowpress' ),
				'recipes'  => $results,
			),
			200
		);
	}

	/**
	 * Find all enabled 'incoming_webhook' recipes whose slug matches.
	 *
	 * @param string $slug Webhook slug.
	 * @return FlowPress_Recipe[]
	 */
	private static function find_recipes_by_slug( string $slug ): array {
		$posts = get_posts(
			array(
				'post_type'      => FlowPress_Recipe_Post_Type::POST_TYPE,
				'post_status'    => 'fp_enabled',
				'posts_per_page' => -1,
				// Recipes store trigger type in _flowpress_trigger (see FlowPress_Recipe::update()).
				'meta_query'     => array(
					array(
						'key'   => '_flowpress_trigger',
						'value' => 'incoming_webhook',
					),
				),
			)
		);

		$matched = array();
		foreach ( $posts as $post ) {
			$recipe = new FlowPress_Recipe( $post );
			$config = get_post_meta( $post->ID, '_fp_trigger_config', true );
			$config = is_array( $config ) ? $config : array();
			if ( ( $config['slug'] ?? '' ) === $slug ) {
				$matched[] = $recipe;
			}
		}

		return $matched;
	}

	/**
	 * Safely retrieve the remote IP address.
	 *
	 * @return string
	 */
	private static function get_remote_ip(): string {
		foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Take the first address when a comma-separated list is present.
				$ip = explode( ',', $ip )[0];
				return trim( $ip );
			}
		}
		return '';
	}
}
