<?php
/**
 * Exception for 431 Request Header Fields Too Large responses
 *
 * @see http://tools.ietf.org/html/draft-nottingham-http-new-status-04
 * @package Requests
 */

/**
 * Exception for 431 Request Header Fields Too Large responses
 *
 * @see http://tools.ietf.org/html/draft-nottingham-http-new-status-04
 * @package Requests
 */
class RequestsExceptionHTTP431 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 431;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Request Header Fields Too Large';
}
