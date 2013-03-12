<?php
/**
 * Exception for 418 I'm A Teapot responses
 *
 * @see http://tools.ietf.org/html/rfc2324
 * @package Requests
 */

/**
 * Exception for 418 I'm A Teapot responses
 *
 * @see http://tools.ietf.org/html/rfc2324
 * @package Requests
 */
class RequestsExceptionHTTP418 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 418;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = "I'm A Teapot";
}
