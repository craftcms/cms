<?php
/**
 * Exception for 429 Too Many Requests responses
 *
 * @see http://tools.ietf.org/html/draft-nottingham-http-new-status-04
 * @package Requests
 */

/**
 * Exception for 429 Too Many Requests responses
 *
 * @see http://tools.ietf.org/html/draft-nottingham-http-new-status-04
 * @package Requests
 */
class RequestsExceptionHTTP429 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 429;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Too Many Requests';
}
