<?php
/**
 * Exception for 504 Gateway Timeout responses
 *
 * @package Requests
 */

/**
 * Exception for 504 Gateway Timeout responses
 *
 * @package Requests
 */
class RequestsExceptionHTTP504 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 504;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Gateway Timeout';
}
