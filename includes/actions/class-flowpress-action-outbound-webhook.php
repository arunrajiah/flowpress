<?php
/**
 * Action: Outbound Webhook.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/actions
 * @since      0.1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Sends an HTTP POST (or GET) request to a configured URL.
 */
class FlowPress_Action_Outbound_Webhook extends FlowPress_Abstract_Action {

	public function get_type(): string {
		return 'outbound_webhook';
	}

	public function get_label(): string {
		return __( 'Send Outbound Webhook', 'flowpress' );
	}

	public function get_description(): string {
		return __( 'Sends an HTTP request to an external URL with an optional JSON body.', 'flowpress' );
	}

	public function get_icon(): string {
		return 'dashicons-migrate';
	}

	public function get_fields(): array {
		return array(
			array(
				'key'         => 'url',
				'label'       => __( 'URL', 'flowpress' ),
				'type'        => 'text',
				'placeholder' => 'https://hooks.example.com/…',
				'tokens'      => true,
			),
			array(
				'key'     => 'method',
				'label'   => __( 'HTTP Method', 'flowpress' ),
				'type'    => 'select',
				'options' => array(
					'POST' => 'POST',
					'GET'  => 'GET',
					'PUT'  => 'PUT',
				),
			),
			array(
				'key'         => 'body',
				'label'       => __( 'Request Body (JSON)', 'flowpress' ),
				'type'        => 'textarea',
				'placeholder' => '{"event":"{{post_title}}"}',
				'tokens'      => true,
			),
			array(
				'key'         => 'secret',
				'label'       => __( 'Secret Key (optional)', 'flowpress' ),
				'type'        => 'text',
				'placeholder' => __( 'Signs request with X-FlowPress-Signature header', 'flowpress' ),
			),
		);
	}

	public function get_summary( array $config ): string {
		$url = $config['url'] ?? '';
		return $url
			? sprintf( __( 'send a webhook to %s', 'flowpress' ), $url )
			: __( 'send a webhook', 'flowpress' );
	}

	public function execute( array $config, array $payload, bool $dry_run = false ): FlowPress_Action_Result {
		$url    = trim( FlowPress_Placeholder::resolve( $config['url']    ?? '', $payload ) );
		$method = strtoupper( $config['method'] ?? 'POST' );
		$body   = FlowPress_Placeholder::resolve( $config['body']   ?? '', $payload );
		$secret = $config['secret'] ?? '';

		if ( empty( $url ) ) {
			return FlowPress_Action_Result::failed( __( 'Webhook URL is required.', 'flowpress' ) );
		}

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return FlowPress_Action_Result::failed( __( 'Webhook URL is not valid.', 'flowpress' ) );
		}

		if ( $dry_run ) {
			return FlowPress_Action_Result::skipped(
				sprintf( __( 'Dry run — would %s to %s', 'flowpress' ), $method, $url )
			);
		}

		$headers = array( 'Content-Type' => 'application/json' );

		if ( ! empty( $secret ) ) {
			$headers['X-FlowPress-Signature'] = 'sha256=' . hash_hmac( 'sha256', $body, $secret );
		}

		$args = array(
			'method'  => $method,
			'headers' => $headers,
			'timeout' => 15,
		);

		if ( 'GET' !== $method ) {
			$args['body'] = $body;
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return FlowPress_Action_Result::failed( $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code >= 200 && $code < 300 ) {
			return FlowPress_Action_Result::success(
				sprintf( __( 'Webhook delivered — HTTP %d', 'flowpress' ), $code )
			);
		}

		return FlowPress_Action_Result::failed(
			sprintf( __( 'Webhook failed — HTTP %d', 'flowpress' ), $code )
		);
	}
}
