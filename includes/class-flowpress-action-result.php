<?php
/**
 * Value object returned by every action execution.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Action_Result
 *
 * @since 0.3.0
 */
class FlowPress_Action_Result {

	const STATUS_SUCCESS = 'success';
	const STATUS_FAILED  = 'failed';
	const STATUS_SKIPPED = 'skipped'; // dry-run or disabled.

	/** @var string */
	private $status;

	/** @var string */
	private $message;

	/** @var array */
	private $data;

	/**
	 * @param string $status  One of STATUS_* constants.
	 * @param string $message Human-readable result message.
	 * @param array  $data    Optional extra context (e.g. resolved email to/subject).
	 */
	public function __construct( $status, $message = '', array $data = array() ) {
		$this->status  = $status;
		$this->message = $message;
		$this->data    = $data;
	}

	/** @return bool */
	public function is_success() {
		return self::STATUS_SUCCESS === $this->status;
	}

	/** @return string */
	public function get_status() {
		return $this->status;
	}

	/** @return string */
	public function get_message() {
		return $this->message;
	}

	/** @return array */
	public function get_data() {
		return $this->data;
	}

	/** @return array Serialisable representation for the run log. */
	public function to_array() {
		return array(
			'status'  => $this->status,
			'message' => $this->message,
			'data'    => $this->data,
		);
	}

	/** @return self */
	public static function success( $message = '', array $data = array() ) {
		return new self( self::STATUS_SUCCESS, $message, $data );
	}

	/** @return self */
	public static function failed( $message, array $data = array() ) {
		return new self( self::STATUS_FAILED, $message, $data );
	}

	/** @return self */
	public static function skipped( $message = '', array $data = array() ) {
		return new self( self::STATUS_SKIPPED, $message, $data );
	}
}
