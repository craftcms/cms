<?php
/**
 * Exception for 511 Network Authentication Required responses
 *
 * @see http://tools.ietf.org/html/draft-nottingham-http-new-status-04
 * @package Requests
 */

/**
 * Exception for 511 Network Authentication Required responses
 *
 * @see http://tools.ietf.org/html/draft-nottingham-http-new-status-04
 * @package Requests
 */
class RequestsExceptionHTTP511 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 511;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Network Authentication Required';
}
