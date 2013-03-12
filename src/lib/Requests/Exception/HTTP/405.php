<?php
/**
 * Exception for 405 Method Not Allowed responses
 *
 * @package Requests
 */

/**
 * Exception for 405 Method Not Allowed responses
 *
 * @package Requests
 */
class RequestsExceptionHTTP405 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 405;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Method Not Allowed';
}
