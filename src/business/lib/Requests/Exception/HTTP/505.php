<?php
/**
 * Exception for 505 HTTP Version Not Supported responses
 *
 * @package Requests
 */

/**
 * Exception for 505 HTTP Version Not Supported responses
 *
 * @package Requests
 */
class RequestsExceptionHTTP505 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 505;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'HTTP Version Not Supported';
}
