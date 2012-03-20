<?php
/**
 * Exception for HTTP requests
 *
 * @package Requests
 */

/**
 * Exception for HTTP requests
 *
 * @package Requests
 */
class RequestsException extends Exception {
	/**
	 * Type of exception
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Data associated with the exception
	 *
	 * @var mixed
	 */
	protected $data;

	/**
	 * Create a new exception
	 *
	 * @param string $message Exception message
	 * @param string $type Exception type
	 * @param mixed $data Associated data
	 */
	public function __construct($message, $type, $data = null) {
		parent::__construct($message, 0);

		$this->type = $type;
		$this->data = $data;
	}

	/**
	 * Like {@see getCode()}, but a string code.
	 *
	 * @codeCoverageIgnore
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Gives any relevant data
	 *
	 * @codeCoverageIgnore
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}
}
