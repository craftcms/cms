<?php
/**
 * Exception for 415 Unsupported Media Type responses
 *
 * @package Requests
 */

/**
 * Exception for 415 Unsupported Media Type responses
 *
 * @package Requests
 */
class RequestsExceptionHTTP415 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 415;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Unsupported Media Type';
}
