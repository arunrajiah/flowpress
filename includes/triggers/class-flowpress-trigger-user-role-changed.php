<?php
/**
 * Trigger: User Role Changed.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/triggers
 * @since      0.1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fires when a user's role is changed by an admin or programmatically.
 */
class FlowPress_Trigger_User_Role_Changed extends FlowPress_Abstract_Trigger {

	public function get_type(): string {
		return 'user_role_changed';
	}

	public function get_label(): string {
		return __( 'User Role Changed', 'flowpress' );
	}

	public function get_description(): string {
		return __( "Fires when a user's role is changed.", 'flowpress' );
	}

	public function get_icon(): string {
		return 'dashicons-admin-users';
	}

	public function get_tokens(): array {
		return array(
			array( 'token' => 'user_id',      'label' => __( 'User ID', 'flowpress' ) ),
			array( 'token' => 'user_login',   'label' => __( 'Username', 'flowpress' ) ),
			array( 'token' => 'user_email',   'label' => __( 'User Email', 'flowpress' ) ),
			array( 'token' => 'display_name', 'label' => __( 'Display Name', 'flowpress' ) ),
			array( 'token' => 'new_role',     'label' => __( 'New Role', 'flowpress' ) ),
			array( 'token' => 'old_role',     'label' => __( 'Previous Role', 'flowpress' ) ),
		);
	}

	public function get_sample_payload(): array {
		return array(
			'user_id'      => '5',
			'user_login'   => 'jsmith',
			'user_email'   => 'jane@example.com',
			'display_name' => 'Jane Smith',
			'new_role'     => 'editor',
			'old_role'     => 'subscriber',
		);
	}

	public function attach(): void {
		add_action( 'set_user_role', array( $this, 'handle' ), 10, 3 );
	}

	/**
	 * @param int    $user_id   User ID.
	 * @param string $new_role  New role slug.
	 * @param array  $old_roles Previous roles array.
	 */
	public function handle( int $user_id, string $new_role, array $old_roles ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		FlowPress_Runner::run(
			$this->get_type(),
			array(
				'user_id'      => (string) $user_id,
				'user_login'   => $user->user_login,
				'user_email'   => $user->user_email,
				'display_name' => $user->display_name,
				'new_role'     => $new_role,
				'old_role'     => ! empty( $old_roles ) ? $old_roles[0] : '',
			)
		);
	}
}
