<?php
/**
 * Tests for FlowPress_Condition_Evaluator.
 *
 * @package FlowPress
 */

/**
 * Class Test_Conditions
 */
class Test_Conditions extends WP_UnitTestCase {

	/** @var FlowPress_Condition_Evaluator */
	private $eval;

	public function set_up() {
		parent::set_up();
		$this->eval = new FlowPress_Condition_Evaluator();
	}

	// ── Empty / no conditions ──────────────────────────────────────────────────

	public function test_no_conditions_returns_true() {
		$this->assertTrue( $this->eval->evaluate( array(), array( 'post_title' => 'Hello' ) ) );
	}

	public function test_empty_items_returns_true() {
		$conds = array( 'logic' => 'AND', 'items' => array() );
		$this->assertTrue( $this->eval->evaluate( $conds, array() ) );
	}

	// ── AND logic ─────────────────────────────────────────────────────────────

	public function test_and_all_pass() {
		$conds = array(
			'logic' => 'AND',
			'items' => array(
				array( 'field' => 'post_title', 'operator' => 'equals', 'value' => 'Hello' ),
				array( 'field' => 'post_status', 'operator' => 'equals', 'value' => 'publish' ),
			),
		);
		$this->assertTrue( $this->eval->evaluate( $conds, array( 'post_title' => 'Hello', 'post_status' => 'publish' ) ) );
	}

	public function test_and_one_fails_returns_false() {
		$conds = array(
			'logic' => 'AND',
			'items' => array(
				array( 'field' => 'post_title', 'operator' => 'equals', 'value' => 'Hello' ),
				array( 'field' => 'post_status', 'operator' => 'equals', 'value' => 'draft' ),
			),
		);
		$this->assertFalse( $this->eval->evaluate( $conds, array( 'post_title' => 'Hello', 'post_status' => 'publish' ) ) );
	}

	// ── OR logic ──────────────────────────────────────────────────────────────

	public function test_or_one_passes_returns_true() {
		$conds = array(
			'logic' => 'OR',
			'items' => array(
				array( 'field' => 'post_title', 'operator' => 'equals', 'value' => 'Nope' ),
				array( 'field' => 'post_status', 'operator' => 'equals', 'value' => 'publish' ),
			),
		);
		$this->assertTrue( $this->eval->evaluate( $conds, array( 'post_title' => 'Hello', 'post_status' => 'publish' ) ) );
	}

	public function test_or_all_fail_returns_false() {
		$conds = array(
			'logic' => 'OR',
			'items' => array(
				array( 'field' => 'post_title', 'operator' => 'equals', 'value' => 'Nope' ),
				array( 'field' => 'post_status', 'operator' => 'equals', 'value' => 'draft' ),
			),
		);
		$this->assertFalse( $this->eval->evaluate( $conds, array( 'post_title' => 'Hello', 'post_status' => 'publish' ) ) );
	}

	// ── Operators ─────────────────────────────────────────────────────────────

	public function test_operator_not_equals() {
		$conds = array( 'logic' => 'AND', 'items' => array(
			array( 'field' => 'post_title', 'operator' => 'not_equals', 'value' => 'Bye' ),
		) );
		$this->assertTrue( $this->eval->evaluate( $conds, array( 'post_title' => 'Hello' ) ) );
	}

	public function test_operator_contains() {
		$conds = array( 'logic' => 'AND', 'items' => array(
			array( 'field' => 'post_title', 'operator' => 'contains', 'value' => 'ello' ),
		) );
		$this->assertTrue( $this->eval->evaluate( $conds, array( 'post_title' => 'Hello' ) ) );
	}

	public function test_operator_not_contains() {
		$conds = array( 'logic' => 'AND', 'items' => array(
			array( 'field' => 'post_title', 'operator' => 'not_contains', 'value' => 'Bye' ),
		) );
		$this->assertTrue( $this->eval->evaluate( $conds, array( 'post_title' => 'Hello' ) ) );
	}

	public function test_operator_starts_with() {
		$conds = array( 'logic' => 'AND', 'items' => array(
			array( 'field' => 'post_title', 'operator' => 'starts_with', 'value' => 'Hell' ),
		) );
		$this->assertTrue( $this->eval->evaluate( $conds, array( 'post_title' => 'Hello' ) ) );
		$this->assertFalse( $this->eval->evaluate( $conds, array( 'post_title' => 'World' ) ) );
	}

	public function test_operator_ends_with() {
		$conds = array( 'logic' => 'AND', 'items' => array(
			array( 'field' => 'post_title', 'operator' => 'ends_with', 'value' => 'llo' ),
		) );
		$this->assertTrue( $this->eval->evaluate( $conds, array( 'post_title' => 'Hello' ) ) );
		$this->assertFalse( $this->eval->evaluate( $conds, array( 'post_title' => 'World' ) ) );
	}

	public function test_operator_greater_than() {
		$conds = array( 'logic' => 'AND', 'items' => array(
			array( 'field' => 'post_id', 'operator' => 'greater_than', 'value' => '5' ),
		) );
		$this->assertTrue( $this->eval->evaluate( $conds, array( 'post_id' => '10' ) ) );
		$this->assertFalse( $this->eval->evaluate( $conds, array( 'post_id' => '3' ) ) );
	}

	public function test_operator_less_than() {
		$conds = array( 'logic' => 'AND', 'items' => array(
			array( 'field' => 'post_id', 'operator' => 'less_than', 'value' => '10' ),
		) );
		$this->assertTrue( $this->eval->evaluate( $conds, array( 'post_id' => '5' ) ) );
		$this->assertFalse( $this->eval->evaluate( $conds, array( 'post_id' => '15' ) ) );
	}

	public function test_operator_is_set() {
		$conds = array( 'logic' => 'AND', 'items' => array(
			array( 'field' => 'post_title', 'operator' => 'is_set', 'value' => '' ),
		) );
		$this->assertTrue( $this->eval->evaluate( $conds, array( 'post_title' => 'Hello' ) ) );
		$this->assertFalse( $this->eval->evaluate( $conds, array( 'post_title' => '' ) ) );
		$this->assertFalse( $this->eval->evaluate( $conds, array() ) );
	}

	public function test_operator_is_not_set() {
		$conds = array( 'logic' => 'AND', 'items' => array(
			array( 'field' => 'post_title', 'operator' => 'is_not_set', 'value' => '' ),
		) );
		$this->assertTrue( $this->eval->evaluate( $conds, array( 'post_title' => '' ) ) );
		$this->assertTrue( $this->eval->evaluate( $conds, array() ) );
		$this->assertFalse( $this->eval->evaluate( $conds, array( 'post_title' => 'Hello' ) ) );
	}

	// ── Missing field defaults to empty string ────────────────────────────────

	public function test_missing_field_treated_as_empty() {
		$conds = array( 'logic' => 'AND', 'items' => array(
			array( 'field' => 'nonexistent', 'operator' => 'equals', 'value' => '' ),
		) );
		$this->assertTrue( $this->eval->evaluate( $conds, array() ) );
	}
}
