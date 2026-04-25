<?php
/**
 * Trigger: WooCommerce Order Placed.
 *
 * Only registered when WooCommerce is active.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/triggers
 * @since      0.1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fires when a customer completes checkout and an order is created.
 */
class FlowPress_Trigger_Woo_Order_Placed extends FlowPress_Abstract_Trigger {

	public function get_type(): string {
		return 'woo_order_placed';
	}

	public function get_label(): string {
		return __( 'WooCommerce Order Placed', 'flowpress' );
	}

	public function get_description(): string {
		return __( 'Fires when a customer completes checkout and a new order is created.', 'flowpress' );
	}

	public function get_icon(): string {
		return 'dashicons-cart';
	}

	public function get_tokens(): array {
		return array(
			array( 'token' => 'order_id',        'label' => __( 'Order ID', 'flowpress' ) ),
			array( 'token' => 'order_number',    'label' => __( 'Order Number', 'flowpress' ) ),
			array( 'token' => 'order_total',     'label' => __( 'Order Total', 'flowpress' ) ),
			array( 'token' => 'order_status',    'label' => __( 'Order Status', 'flowpress' ) ),
			array( 'token' => 'order_currency',  'label' => __( 'Currency', 'flowpress' ) ),
			array( 'token' => 'customer_email',  'label' => __( 'Customer Email', 'flowpress' ) ),
			array( 'token' => 'customer_name',   'label' => __( 'Customer Full Name', 'flowpress' ) ),
			array( 'token' => 'billing_country', 'label' => __( 'Billing Country', 'flowpress' ) ),
			array( 'token' => 'payment_method',  'label' => __( 'Payment Method', 'flowpress' ) ),
		);
	}

	public function get_sample_payload(): array {
		return array(
			'order_id'        => '101',
			'order_number'    => '101',
			'order_total'     => '49.99',
			'order_status'    => 'processing',
			'order_currency'  => 'USD',
			'customer_email'  => 'buyer@example.com',
			'customer_name'   => 'John Buyer',
			'billing_country' => 'US',
			'payment_method'  => 'stripe',
		);
	}

	public function attach(): void {
		// Guard: only attach hook when WooCommerce is present.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'handle' ), 10, 1 );
	}

	/**
	 * @param int $order_id WooCommerce order ID.
	 */
	public function handle( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		FlowPress_Runner::run(
			$this->get_type(),
			array(
				'order_id'        => (string) $order_id,
				'order_number'    => $order->get_order_number(),
				'order_total'     => $order->get_total(),
				'order_status'    => $order->get_status(),
				'order_currency'  => $order->get_currency(),
				'customer_email'  => $order->get_billing_email(),
				'customer_name'   => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				'billing_country' => $order->get_billing_country(),
				'payment_method'  => $order->get_payment_method(),
			)
		);
	}
}
