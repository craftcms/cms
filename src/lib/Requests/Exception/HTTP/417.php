<?php
/**
 * Exception for 417 Expectation Failed responses
 *
 * @package Requests
 */

/**
 * Exception for 417 Expectation Failed responses
 *
 * @package Requests
 */
class RequestsExceptionHTTP417 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 417;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Expectation Failed';
}
