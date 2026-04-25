<?php
/**
 * Action: WooCommerce Create Coupon.
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
 * Creates a WooCommerce coupon programmatically.
 */
class FlowPress_Action_Woo_Create_Coupon extends FlowPress_Abstract_Action {

	public function get_type(): string {
		return 'woo_create_coupon';
	}

	public function get_label(): string {
		return __( 'Create WooCommerce Coupon', 'flowpress' );
	}

	public function get_description(): string {
		return __( 'Generates a new discount coupon in WooCommerce.', 'flowpress' );
	}

	public function get_icon(): string {
		return 'dashicons-tag';
	}

	public function get_fields(): array {
		return array(
			array(
				'key'         => 'code',
				'label'       => __( 'Coupon Code', 'flowpress' ),
				'type'        => 'text',
				'placeholder' => 'WELCOME10',
				'tokens'      => true,
			),
			array(
				'key'     => 'discount_type',
				'label'   => __( 'Discount Type', 'flowpress' ),
				'type'    => 'select',
				'options' => array(
					'percent'       => __( 'Percentage discount', 'flowpress' ),
					'fixed_cart'    => __( 'Fixed cart discount', 'flowpress' ),
					'fixed_product' => __( 'Fixed product discount', 'flowpress' ),
				),
			),
			array(
				'key'         => 'amount',
				'label'       => __( 'Amount', 'flowpress' ),
				'type'        => 'text',
				'placeholder' => '10',
				'tokens'      => true,
			),
			array(
				'key'         => 'expiry_date',
				'label'       => __( 'Expiry Date (YYYY-MM-DD)', 'flowpress' ),
				'type'        => 'text',
				'placeholder' => '2025-12-31',
				'tokens'      => true,
			),
			array(
				'key'         => 'usage_limit',
				'label'       => __( 'Usage Limit (blank = unlimited)', 'flowpress' ),
				'type'        => 'text',
				'placeholder' => '1',
			),
		);
	}

	public function get_summary( array $config ): string {
		$code = $config['code'] ?? '';
		return $code
			? sprintf( __( 'create coupon "%s"', 'flowpress' ), $code )
			: __( 'create a WooCommerce coupon', 'flowpress' );
	}

	public function execute( array $config, array $payload, bool $dry_run = false ): FlowPress_Action_Result {
		if ( ! class_exists( 'WC_Coupon' ) ) {
			return FlowPress_Action_Result::failed( __( 'WooCommerce is not active.', 'flowpress' ) );
		}

		$code   = strtoupper( trim( FlowPress_Placeholder::resolve( $config['code']   ?? '', $payload ) ) );
		$amount = trim( FlowPress_Placeholder::resolve( $config['amount'] ?? '', $payload ) );
		$expiry = trim( FlowPress_Placeholder::resolve( $config['expiry_date'] ?? '', $payload ) );
		$type   = $config['discount_type'] ?? 'percent';
		$limit  = isset( $config['usage_limit'] ) && '' !== $config['usage_limit']
			? (int) $config['usage_limit']
			: 0;

		if ( empty( $code ) ) {
			return FlowPress_Action_Result::failed( __( 'Coupon code is required.', 'flowpress' ) );
		}

		if ( $dry_run ) {
			return FlowPress_Action_Result::skipped(
				sprintf( __( 'Dry run — would create coupon "%s"', 'flowpress' ), $code )
			);
		}

		$coupon = new WC_Coupon();
		$coupon->set_code( $code );
		$coupon->set_discount_type( $type );
		$coupon->set_amount( (float) $amount );

		if ( ! empty( $expiry ) ) {
			$coupon->set_date_expires( $expiry );
		}

		if ( $limit > 0 ) {
			$coupon->set_usage_limit( $limit );
		}

		$coupon_id = $coupon->save();

		if ( ! $coupon_id ) {
			return FlowPress_Action_Result::failed( __( 'Failed to save coupon.', 'flowpress' ) );
		}

		return FlowPress_Action_Result::success(
			sprintf( __( 'Coupon "%s" created (ID %d).', 'flowpress' ), $code, $coupon_id ),
			array( 'coupon_id' => $coupon_id, 'code' => $code )
		);
	}
}
