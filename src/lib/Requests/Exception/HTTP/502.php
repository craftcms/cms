<?php
/**
 * Exception for 502 Bad Gateway responses
 *
 * @package Requests
 */

/**
 * Exception for 502 Bad Gateway responses
 *
 * @package Requests
 */
class RequestsExceptionHTTP502 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 502;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Bad Gateway';
}
