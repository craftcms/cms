<?php
/**
 * Exception for 410 Gone responses
 *
 * @package Requests
 */

/**
 * Exception for 410 Gone responses
 *
 * @package Requests
 */
class RequestsExceptionHTTP410 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 410;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Gone';
}
