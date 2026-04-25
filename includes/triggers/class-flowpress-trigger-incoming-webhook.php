<?php
/**
 * Trigger: Incoming Webhook.
 *
 * Exposes a REST endpoint so external services can fire a recipe.
 * Endpoint: POST /wp-json/flowpress/v1/webhook/{slug}
 *
 * Each recipe stores a unique slug in its trigger config. FlowPress_Incoming_Webhook
 * registers the REST route and dispatches to FlowPress_Runner on receipt.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/triggers
 * @since      0.1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fires when an authorised HTTP POST request hits the recipe's webhook URL.
 */
class FlowPress_Trigger_Incoming_Webhook extends FlowPress_Abstract_Trigger {

	public function get_type(): string {
		return 'incoming_webhook';
	}

	public function get_label(): string {
		return __( 'Incoming Webhook', 'flowpress' );
	}

	public function get_description(): string {
		return __( 'Fires when an external service sends an HTTP POST to this recipe\'s unique webhook URL.', 'flowpress' );
	}

	public function get_icon(): string {
		return 'dashicons-rest-api';
	}

	public function get_tokens(): array {
		return array(
			array( 'token' => 'webhook_slug',    'label' => __( 'Webhook Slug', 'flowpress' ) ),
			array( 'token' => 'payload_raw',     'label' => __( 'Raw Payload (JSON)', 'flowpress' ) ),
			array( 'token' => 'remote_ip',       'label' => __( 'Sender IP Address', 'flowpress' ) ),
		);
	}

	public function get_sample_payload(): array {
		return array(
			'webhook_slug' => 'my-recipe-slug',
			'payload_raw'  => '{"event":"test","data":{}}',
			'remote_ip'    => '1.2.3.4',
		);
	}

	public function get_fields(): array {
		return array(
			array(
				'key'         => 'slug',
				'label'       => __( 'Webhook Slug', 'flowpress' ),
				'type'        => 'text',
				'placeholder' => __( 'my-recipe-slug', 'flowpress' ),
				'help'        => __( 'Unique identifier used in the webhook URL. Use lowercase letters, numbers and hyphens only.', 'flowpress' ),
			),
		);
	}

	/**
	 * Attach: register the REST route.
	 * The actual dispatch lives in FlowPress_Incoming_Webhook.
	 */
	public function attach(): void {
		// REST route is registered by FlowPress_Incoming_Webhook::register_routes()
		// which is called via the rest_api_init hook in class-flowpress.php.
	}
}
