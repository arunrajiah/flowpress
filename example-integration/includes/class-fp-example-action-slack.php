<?php
/**
 * Example action: Send a Slack message via incoming webhook.
 *
 * Demonstrates how to implement a FlowPress action that calls an external API.
 * Uses Slack's Incoming Webhooks (https://api.slack.com/messaging/webhooks).
 *
 * @package FP_Example_Integration
 * @since   0.1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Sends a message to a Slack channel via an Incoming Webhook URL.
 */
class FP_Example_Action_Slack extends FlowPress_Abstract_Action {

	public function get_type(): string {
		return 'fp_example_slack_message';
	}

	public function get_label(): string {
		return __( 'Send Slack Message', 'fp-example-integration' );
	}

	public function get_description(): string {
		return __( 'Posts a message to a Slack channel via an Incoming Webhook URL.', 'fp-example-integration' );
	}

	public function get_icon(): string {
		return 'dashicons-format-chat';
	}

	public function get_fields(): array {
		return array(
			array(
				'key'         => 'webhook_url',
				'label'       => __( 'Slack Webhook URL', 'fp-example-integration' ),
				'type'        => 'text',
				'placeholder' => 'https://hooks.slack.com/services/…',
			),
			array(
				'key'         => 'message',
				'label'       => __( 'Message', 'fp-example-integration' ),
				'type'        => 'textarea',
				'placeholder' => __( 'New submission from {{sender_name}}: {{message}}', 'fp-example-integration' ),
				'tokens'      => true,
			),
			array(
				'key'         => 'username',
				'label'       => __( 'Bot Name (optional)', 'fp-example-integration' ),
				'type'        => 'text',
				'placeholder' => 'FlowPress Bot',
			),
		);
	}

	public function get_summary( array $config ): string {
		return __( 'send a Slack message', 'fp-example-integration' );
	}

	public function execute( array $config, array $payload, bool $dry_run ): FlowPress_Action_Result {
		$webhook_url = trim( $config['webhook_url'] ?? '' );
		$message     = trim( FlowPress_Placeholder::resolve( $config['message'] ?? '', $payload ) );
		$username    = trim( $config['username'] ?? 'FlowPress' );

		if ( empty( $webhook_url ) ) {
			return FlowPress_Action_Result::failed(
				__( 'Slack webhook URL is required.', 'fp-example-integration' )
			);
		}

		if ( ! filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
			return FlowPress_Action_Result::failed(
				__( 'Slack webhook URL is not valid.', 'fp-example-integration' )
			);
		}

		if ( empty( $message ) ) {
			return FlowPress_Action_Result::failed(
				__( 'Message text is required.', 'fp-example-integration' )
			);
		}

		if ( $dry_run ) {
			return FlowPress_Action_Result::skipped(
				__( 'Dry run — Slack message not sent.', 'fp-example-integration' )
			);
		}

		$body = wp_json_encode(
			array(
				'text'     => $message,
				'username' => $username,
			)
		);

		$response = wp_remote_post(
			$webhook_url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => $body,
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return FlowPress_Action_Result::failed( $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 === (int) $code ) {
			return FlowPress_Action_Result::success(
				__( 'Slack message sent successfully.', 'fp-example-integration' )
			);
		}

		return FlowPress_Action_Result::failed(
			sprintf(
				/* translators: %d: HTTP response code */
				__( 'Slack returned HTTP %d.', 'fp-example-integration' ),
				$code
			)
		);
	}
}
