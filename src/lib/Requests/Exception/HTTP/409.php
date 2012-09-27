<?php
/**
 * Exception for 409 Conflict responses
 *
 * @package Requests
 */

/**
 * Exception for 409 Conflict responses
 *
 * @package Requests
 */
class RequestsExceptionHTTP409 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 409;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Conflict';
}
