<?php
/**
 * Action: send an email.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/actions
 * @since      0.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Action_Send_Email
 *
 * Sends an email via wp_mail(). Supports {{token}} placeholders in
 * the To, Subject, and Body fields.
 *
 * @since 0.3.0
 */
class FlowPress_Action_Send_Email extends FlowPress_Abstract_Action {

	/** @inheritDoc */
	public function get_type() {
		return 'send_email';
	}

	/** @inheritDoc */
	public function get_icon() {
		return 'dashicons-email-alt';
	}

	/** @inheritDoc */
	public function get_label() {
		return __( 'Send an email', 'flowpress' );
	}

	/** @inheritDoc */
	public function get_description() {
		return __( 'Sends an email to any address. Supports placeholders from the trigger.', 'flowpress' );
	}

	/** @inheritDoc */
	public function get_summary( array $config ) {
		$to = ! empty( $config['to'] ) ? $config['to'] : __( 'someone', 'flowpress' );
		/* translators: %s: email recipient. */
		return sprintf( __( 'send an email to %s', 'flowpress' ), $to );
	}

	/** @inheritDoc */
	public function get_fields() {
		return array(
			array(
				'key'         => 'to',
				'label'       => __( 'To', 'flowpress' ),
				'type'        => 'text',
				'required'    => true,
				'placeholder' => 'you@example.com',
				'help'        => __( 'The recipient email address. Supports {{token}} placeholders.', 'flowpress' ),
			),
			array(
				'key'         => 'subject',
				'label'       => __( 'Subject', 'flowpress' ),
				'type'        => 'text',
				'required'    => true,
				'placeholder' => __( 'New post: {{post_title}}', 'flowpress' ),
				'help'        => __( 'The email subject line. Supports {{token}} placeholders.', 'flowpress' ),
			),
			array(
				'key'         => 'body',
				'label'       => __( 'Body', 'flowpress' ),
				'type'        => 'textarea',
				'required'    => true,
				'placeholder' => __( "A new post was published on {{site_name}}:\n\n{{post_title}}\n{{post_url}}", 'flowpress' ),
				'help'        => __( 'The email body. Supports {{token}} placeholders. Plain text only.', 'flowpress' ),
			),
		);
	}

	/** @inheritDoc */
	public function execute( array $config, array $payload, bool $dry_run = false ): FlowPress_Action_Result {
		$to      = sanitize_email( $config['to'] ?? '' );
		$subject = sanitize_text_field( $config['subject'] ?? '' );
		$body    = sanitize_textarea_field( $config['body'] ?? '' );

		if ( ! $to || ! is_email( $to ) ) {
			return FlowPress_Action_Result::failed(
				sprintf(
					/* translators: %s: the invalid email address. */
					__( 'Invalid "To" email address: "%s".', 'flowpress' ),
					$config['to'] ?? ''
				)
			);
		}

		if ( ! $subject ) {
			return FlowPress_Action_Result::failed( __( 'Email subject is required.', 'flowpress' ) );
		}

		if ( ! $body ) {
			return FlowPress_Action_Result::failed( __( 'Email body is required.', 'flowpress' ) );
		}

		$data = array(
			'to'      => $to,
			'subject' => $subject,
			'body'    => $body,
		);

		if ( $dry_run ) {
			return FlowPress_Action_Result::skipped(
				__( 'Dry run — email not sent.', 'flowpress' ),
				$data
			);
		}

		$sent = wp_mail( $to, $subject, $body );

		if ( $sent ) {
			return FlowPress_Action_Result::success(
				sprintf(
					/* translators: %s: recipient email address. */
					__( 'Email sent to %s.', 'flowpress' ),
					$to
				),
				$data
			);
		}

		// wp_mail() doesn't give us a useful error; surface the last PHP mailer error if available.
		global $phpmailer;
		$mailer_error = '';
		if ( isset( $phpmailer ) && $phpmailer instanceof PHPMailer\PHPMailer\PHPMailer ) {
			$mailer_error = $phpmailer->ErrorInfo;
		}

		return FlowPress_Action_Result::failed(
			$mailer_error ?: __( 'wp_mail() returned false. Check your server mail configuration.', 'flowpress' ),
			$data
		);
	}
}
