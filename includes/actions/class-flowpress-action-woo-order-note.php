<?php
/**
 * Action: WooCommerce Add Order Note.
 *
 * Only registered when WooCommerce is active.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/actions
 * @since      0.1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Adds a note to a WooCommerce order.
 */
class FlowPress_Action_Woo_Order_Note extends FlowPress_Abstract_Action {

	public function get_type(): string {
		return 'woo_order_note';
	}

	public function get_label(): string {
		return __( 'Add WooCommerce Order Note', 'flowpress' );
	}

	public function get_description(): string {
		return __( 'Adds an internal or customer-facing note to a WooCommerce order.', 'flowpress' );
	}

	public function get_icon(): string {
		return 'dashicons-format-chat';
	}

	public function get_fields(): array {
		return array(
			array(
				'key'         => 'order_id',
				'label'       => __( 'Order ID', 'flowpress' ),
				'type'        => 'text',
				'placeholder' => '{{order_id}}',
				'tokens'      => true,
			),
			array(
				'key'         => 'note',
				'label'       => __( 'Note', 'flowpress' ),
				'type'        => 'textarea',
				'placeholder' => __( 'Automation note: {{post_title}}', 'flowpress' ),
				'tokens'      => true,
			),
			array(
				'key'     => 'note_type',
				'label'   => __( 'Note Type', 'flowpress' ),
				'type'    => 'select',
				'options' => array(
					'internal' => __( 'Internal note (admin only)', 'flowpress' ),
					'customer' => __( 'Customer note (visible to buyer)', 'flowpress' ),
				),
			),
		);
	}

	public function get_summary( array $config ): string {
		return __( 'add a note to an order', 'flowpress' );
	}

	public function execute( array $config, array $payload, bool $dry_run = false ): FlowPress_Action_Result {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return FlowPress_Action_Result::failed( __( 'WooCommerce is not active.', 'flowpress' ) );
		}

		$order_id = (int) FlowPress_Placeholder::resolve( $config['order_id'] ?? '', $payload );
		$note     = FlowPress_Placeholder::resolve( $config['note'] ?? '', $payload );
		$is_customer_note = ( ( $config['note_type'] ?? 'internal' ) === 'customer' );

		if ( ! $order_id ) {
			return FlowPress_Action_Result::failed( __( 'Order ID is required.', 'flowpress' ) );
		}

		if ( empty( $note ) ) {
			return FlowPress_Action_Result::failed( __( 'Note text is required.', 'flowpress' ) );
		}

		if ( $dry_run ) {
			return FlowPress_Action_Result::skipped(
				sprintf( __( 'Dry run — would add note to order #%d', 'flowpress' ), $order_id )
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return FlowPress_Action_Result::failed(
				sprintf( __( 'Order #%d not found.', 'flowpress' ), $order_id )
			);
		}

		$note_id = $order->add_order_note( $note, $is_customer_note ? 1 : 0, $is_customer_note );

		if ( ! $note_id ) {
			return FlowPress_Action_Result::failed( __( 'Failed to add order note.', 'flowpress' ) );
		}

		return FlowPress_Action_Result::success(
			sprintf( __( 'Note added to order #%d.', 'flowpress' ), $order_id ),
			array( 'note_id' => $note_id )
		);
	}
}
