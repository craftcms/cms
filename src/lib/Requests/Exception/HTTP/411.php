<?php
/**
 * Exception for 411 Length Required responses
 *
 * @package Requests
 */

/**
 * Exception for 411 Length Required responses
 *
 * @package Requests
 */
class RequestsExceptionHTTP411 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 411;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Length Required';
}
