<?php
/**
 * Minimal WP_Error for the unit suite (no WordPress loaded).
 */
class WP_Error {

	/**
	 * Error code
	 *
	 * @var string
	 */
	public $code;

	/**
	 * Error message
	 *
	 * @var string
	 */
	public $message;

	public function __construct( $code = '', $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}

	public function get_error_code() {
		return $this->code;
	}
}
