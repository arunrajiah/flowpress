<?php
/**
 * Tests for the FlowPress trigger/action engine.
 *
 * @package FlowPress
 */

/**
 * Class Test_Runner
 *
 * Integration-style tests for the recipe runner, placeholder resolver,
 * and action result value object.
 *
 * @since 0.3.0
 */
class Test_Runner extends WP_UnitTestCase {

	// -------------------------------------------------------------------------
	// FlowPress_Placeholder
	// -------------------------------------------------------------------------

	public function test_placeholder_resolves_known_token() {
		$result = FlowPress_Placeholder::resolve( 'Hello {{name}}!', array( 'name' => 'World' ) );
		$this->assertSame( 'Hello World!', $result );
	}

	public function test_placeholder_leaves_unknown_token_intact() {
		$result = FlowPress_Placeholder::resolve( '{{unknown}}', array() );
		$this->assertSame( '{{unknown}}', $result );
	}

	public function test_placeholder_resolves_multiple_tokens() {
		$result = FlowPress_Placeholder::resolve(
			'{{post_title}} at {{post_url}}',
			array(
				'post_title' => 'Hello',
				'post_url'   => 'https://example.com/hello',
			)
		);
		$this->assertSame( 'Hello at https://example.com/hello', $result );
	}

	public function test_placeholder_extract_tokens() {
		$tokens = FlowPress_Placeholder::extract_tokens( 'Hi {{name}}, see {{post_url}}' );
		$this->assertContains( 'name', $tokens );
		$this->assertContains( 'post_url', $tokens );
		$this->assertCount( 2, $tokens );
	}

	// -------------------------------------------------------------------------
	// FlowPress_Action_Result
	// -------------------------------------------------------------------------

	public function test_action_result_success() {
		$result = FlowPress_Action_Result::success( 'done' );
		$this->assertTrue( $result->is_success() );
		$this->assertSame( FlowPress_Action_Result::STATUS_SUCCESS, $result->get_status() );
		$this->assertSame( 'done', $result->get_message() );
	}

	public function test_action_result_failed() {
		$result = FlowPress_Action_Result::failed( 'bad' );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( FlowPress_Action_Result::STATUS_FAILED, $result->get_status() );
	}

	public function test_action_result_skipped() {
		$result = FlowPress_Action_Result::skipped( 'dry' );
		$this->assertSame( FlowPress_Action_Result::STATUS_SKIPPED, $result->get_status() );
	}

	public function test_action_result_to_array() {
		$result = FlowPress_Action_Result::success( 'ok', array( 'to' => 'a@b.com' ) );
		$arr    = $result->to_array();
		$this->assertSame( 'success', $arr['status'] );
		$this->assertSame( 'ok', $arr['message'] );
		$this->assertSame( 'a@b.com', $arr['data']['to'] );
	}

	// -------------------------------------------------------------------------
	// FlowPress_Action_Send_Email — dry-run
	// -------------------------------------------------------------------------

	public function test_send_email_dry_run_returns_skipped() {
		$action = new FlowPress_Action_Send_Email();
		$result = $action->execute(
			array(
				'to'      => 'test@example.com',
				'subject' => 'Hello',
				'body'    => 'World',
			),
			array(),
			true // dry run.
		);

		$this->assertSame( FlowPress_Action_Result::STATUS_SKIPPED, $result->get_status() );
	}

	public function test_send_email_invalid_to_returns_failed() {
		$action = new FlowPress_Action_Send_Email();
		$result = $action->execute(
			array(
				'to'      => 'not-an-email',
				'subject' => 'Hello',
				'body'    => 'World',
			),
			array(),
			false
		);

		$this->assertSame( FlowPress_Action_Result::STATUS_FAILED, $result->get_status() );
	}

	public function test_send_email_empty_subject_returns_failed() {
		$action = new FlowPress_Action_Send_Email();
		$result = $action->execute(
			array(
				'to'      => 'test@example.com',
				'subject' => '',
				'body'    => 'World',
			),
			array(),
			false
		);

		$this->assertSame( FlowPress_Action_Result::STATUS_FAILED, $result->get_status() );
	}

	// -------------------------------------------------------------------------
	// FlowPress_Runner — recipe execution
	// -------------------------------------------------------------------------

	public function test_runner_logs_skipped_for_disabled_recipe() {
		// Create a disabled recipe with the post_published trigger.
		$recipe = FlowPress_Recipe::create(
			array(
				'title'       => 'Disabled test recipe',
				'description' => '',
			)
		);

		$this->assertNotWPError( $recipe );

		$recipe->update(
			array(
				'status'  => FlowPress_Recipe::STATUS_DISABLED,
				'trigger' => 'post_published',
				'actions' => array(
					array(
						'type'   => 'send_email',
						'config' => array(
							'to'      => 'test@example.com',
							'subject' => 'Test',
							'body'    => 'Test',
						),
					),
				),
			)
		);

		// Run the runner. The recipe is disabled so no real email fires.
		FlowPress_Runner::run( 'post_published', array( 'post_title' => 'Test' ) );

		$logs = FlowPress_Run_Log::get_for_recipe( $recipe->get_id(), 5 );

		$this->assertNotEmpty( $logs );
		$this->assertSame( FlowPress_Run_Log::STATUS_SKIPPED, $logs[0]->status );

		// Cleanup.
		FlowPress_Run_Log::delete_for_recipe( $recipe->get_id() );
		$recipe->delete();
	}

	public function test_runner_dry_run_logs_dry_run_status() {
		$recipe = FlowPress_Recipe::create( array( 'title' => 'Dry run test' ) );
		$this->assertNotWPError( $recipe );

		$recipe->update(
			array(
				'status'  => FlowPress_Recipe::STATUS_ENABLED,
				'trigger' => 'post_published',
				'actions' => array(
					array(
						'type'   => 'send_email',
						'config' => array(
							'to'      => 'test@example.com',
							'subject' => 'Hello {{post_title}}',
							'body'    => 'See: {{post_url}}',
						),
					),
				),
			)
		);

		FlowPress_Runner::execute_recipe(
			$recipe,
			'post_published',
			array( 'post_title' => 'Hello', 'post_url' => 'https://example.com' ),
			true // dry run.
		);

		$logs = FlowPress_Run_Log::get_for_recipe( $recipe->get_id(), 5 );

		$this->assertNotEmpty( $logs );
		$this->assertSame( FlowPress_Run_Log::STATUS_DRY_RUN, $logs[0]->status );
		$this->assertSame( '1', (string) $logs[0]->is_dry_run );

		// Cleanup.
		FlowPress_Run_Log::delete_for_recipe( $recipe->get_id() );
		$recipe->delete();
	}
}
