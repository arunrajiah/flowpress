<?php
/**
 * Trigger: User Registered.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/triggers
 * @since      0.1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fires immediately after a new user account is created.
 */
class FlowPress_Trigger_User_Registered extends FlowPress_Abstract_Trigger {

	public function get_type(): string {
		return 'user_registered';
	}

	public function get_label(): string {
		return __( 'User Registered', 'flowpress' );
	}

	public function get_description(): string {
		return __( 'Fires when a new user account is created on the site.', 'flowpress' );
	}

	public function get_icon(): string {
		return 'dashicons-admin-users';
	}

	public function get_tokens(): array {
		return array(
			array( 'token' => 'user_id',       'label' => __( 'User ID', 'flowpress' ) ),
			array( 'token' => 'user_login',    'label' => __( 'Username', 'flowpress' ) ),
			array( 'token' => 'user_email',    'label' => __( 'User Email', 'flowpress' ) ),
			array( 'token' => 'display_name',  'label' => __( 'Display Name', 'flowpress' ) ),
			array( 'token' => 'user_role',     'label' => __( 'User Role', 'flowpress' ) ),
		);
	}

	public function get_sample_payload(): array {
		return array(
			'user_id'      => '5',
			'user_login'   => 'jsmith',
			'user_email'   => 'jane@example.com',
			'display_name' => 'Jane Smith',
			'user_role'    => 'subscriber',
		);
	}

	public function attach(): void {
		add_action( 'user_register', array( $this, 'handle' ), 10, 1 );
	}

	/**
	 * @param int $user_id Newly created user ID.
	 */
	public function handle( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$roles = $user->roles;

		FlowPress_Runner::run(
			$this->get_type(),
			array(
				'user_id'      => (string) $user_id,
				'user_login'   => $user->user_login,
				'user_email'   => $user->user_email,
				'display_name' => $user->display_name,
				'user_role'    => ! empty( $roles ) ? $roles[0] : '',
			)
		);
	}
}
