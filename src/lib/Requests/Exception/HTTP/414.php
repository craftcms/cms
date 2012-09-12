<?php
/**
 * Exception for 414 Request-URI Too Large responses
 *
 * @package Requests
 */

/**
 * Exception for 414 Request-URI Too Large responses
 *
 * @package Requests
 */
class RequestsExceptionHTTP414 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 414;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Request-URI Too Large';
}
