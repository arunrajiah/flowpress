<?php
/**
 * Evaluates recipe conditions against a trigger payload.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.5.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Condition_Evaluator
 *
 * Conditions are stored on a recipe as:
 * {
 *   "logic": "AND"|"OR",
 *   "items": [
 *     { "field": "post_title", "operator": "contains", "value": "hello" },
 *     ...
 *   ]
 * }
 *
 * Supported operators:
 *   equals, not_equals, contains, not_contains,
 *   starts_with, ends_with, greater_than, less_than,
 *   is_set, is_not_set
 *
 * @since 0.5.0
 */
class FlowPress_Condition_Evaluator {

	const LOGIC_AND = 'AND';
	const LOGIC_OR  = 'OR';

	/**
	 * All supported operators with human labels.
	 *
	 * @since  0.5.0
	 * @return array<string, string>
	 */
	public static function get_operators() {
		return array(
			'equals'       => __( 'equals', 'flowpress' ),
			'not_equals'   => __( 'does not equal', 'flowpress' ),
			'contains'     => __( 'contains', 'flowpress' ),
			'not_contains' => __( 'does not contain', 'flowpress' ),
			'starts_with'  => __( 'starts with', 'flowpress' ),
			'ends_with'    => __( 'ends with', 'flowpress' ),
			'greater_than' => __( 'is greater than', 'flowpress' ),
			'less_than'    => __( 'is less than', 'flowpress' ),
			'is_set'       => __( 'is set (not empty)', 'flowpress' ),
			'is_not_set'   => __( 'is not set (empty)', 'flowpress' ),
		);
	}

	/**
	 * Operators that do not require a comparison value field.
	 *
	 * @since  0.5.0
	 * @return string[]
	 */
	public static function get_valueless_operators() {
		return array( 'is_set', 'is_not_set' );
	}

	/**
	 * Evaluate the full conditions block.
	 *
	 * Returns true if the recipe should run, false if it should be skipped.
	 *
	 * @since  0.5.0
	 * @param  array $conditions The `_flowpress_conditions` meta value.
	 * @param  array $payload    Trigger payload.
	 * @return bool
	 */
	public static function evaluate( array $conditions, array $payload ) {
		$items = $conditions['items'] ?? array();

		if ( empty( $items ) ) {
			return true; // No conditions → always run.
		}

		$logic = strtoupper( $conditions['logic'] ?? self::LOGIC_AND );

		foreach ( $items as $item ) {
			$result = self::evaluate_item( $item, $payload );

			if ( self::LOGIC_OR === $logic && $result ) {
				return true; // Short-circuit OR.
			}

			if ( self::LOGIC_AND === $logic && ! $result ) {
				return false; // Short-circuit AND.
			}
		}

		// AND: all passed. OR: none passed.
		return self::LOGIC_AND === $logic;
	}

	/**
	 * Evaluate a single condition item.
	 *
	 * @since  0.5.0
	 * @param  array $item    Condition item with field, operator, value keys.
	 * @param  array $payload Trigger payload.
	 * @return bool
	 */
	private static function evaluate_item( array $item, array $payload ) {
		$field    = $item['field']    ?? '';
		$operator = $item['operator'] ?? 'equals';
		$expected = $item['value']    ?? '';

		// Resolve the field value from the payload.
		$actual = array_key_exists( $field, $payload ) ? (string) $payload[ $field ] : '';

		switch ( $operator ) {
			case 'equals':
				return $actual === $expected;

			case 'not_equals':
				return $actual !== $expected;

			case 'contains':
				return '' !== $expected && false !== strpos( $actual, $expected );

			case 'not_contains':
				return '' === $expected || false === strpos( $actual, $expected );

			case 'starts_with':
				return '' !== $expected && 0 === strpos( $actual, $expected );

			case 'ends_with':
				$len = strlen( $expected );
				return '' !== $expected && substr( $actual, -$len ) === $expected;

			case 'greater_than':
				return is_numeric( $actual ) && is_numeric( $expected ) && (float) $actual > (float) $expected;

			case 'less_than':
				return is_numeric( $actual ) && is_numeric( $expected ) && (float) $actual < (float) $expected;

			case 'is_set':
				return '' !== $actual;

			case 'is_not_set':
				return '' === $actual;

			default:
				return false;
		}
	}
}
