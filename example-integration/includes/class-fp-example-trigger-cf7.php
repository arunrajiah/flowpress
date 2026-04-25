<?php
/**
 * Example trigger: Contact Form 7 form submitted.
 *
 * Demonstrates how to wrap a third-party plugin hook into a FlowPress trigger.
 * This class is intentionally kept simple so developers can use it as a template.
 *
 * @package FP_Example_Integration
 * @since   0.1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fires when a Contact Form 7 form is submitted successfully.
 */
class FP_Example_Trigger_CF7 extends FlowPress_Abstract_Trigger {

	public function get_type(): string {
		return 'fp_example_cf7_submitted';
	}

	public function get_label(): string {
		return __( 'CF7: Form Submitted', 'fp-example-integration' );
	}

	public function get_description(): string {
		return __( 'Fires when a Contact Form 7 form is submitted successfully.', 'fp-example-integration' );
	}

	public function get_icon(): string {
		return 'dashicons-feedback';
	}

	public function get_tokens(): array {
		return array(
			array( 'token' => 'form_id',    'label' => __( 'Form ID', 'fp-example-integration' ) ),
			array( 'token' => 'form_title', 'label' => __( 'Form Title', 'fp-example-integration' ) ),
			array( 'token' => 'sender_name',  'label' => __( 'Sender Name (your-name field)', 'fp-example-integration' ) ),
			array( 'token' => 'sender_email', 'label' => __( 'Sender Email (your-email field)', 'fp-example-integration' ) ),
			array( 'token' => 'message',      'label' => __( 'Message (your-message field)', 'fp-example-integration' ) ),
		);
	}

	public function get_sample_payload(): array {
		return array(
			'form_id'      => '123',
			'form_title'   => 'Contact Us',
			'sender_name'  => 'Alice Example',
			'sender_email' => 'alice@example.com',
			'message'      => 'Hello, I have a question!',
		);
	}

	public function attach(): void {
		// Guard: only attach when CF7 is active.
		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			return;
		}

		add_action( 'wpcf7_mail_sent', array( $this, 'handle' ) );
	}

	/**
	 * @param WPCF7_ContactForm $contact_form The submitted CF7 form object.
	 */
	public function handle( $contact_form ): void {
		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return;
		}

		$posted = $submission->get_posted_data();

		FlowPress_Runner::run(
			$this->get_type(),
			array(
				'form_id'      => (string) $contact_form->id(),
				'form_title'   => $contact_form->title(),
				'sender_name'  => $posted['your-name']    ?? '',
				'sender_email' => $posted['your-email']   ?? '',
				'message'      => $posted['your-message'] ?? '',
			)
		);
	}
}
