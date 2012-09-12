<?php
/**
 * Exception for 416 Requested Range Not Satisfiable responses
 *
 * @package Requests
 */

/**
 * Exception for 416 Requested Range Not Satisfiable responses
 *
 * @package Requests
 */
class RequestsExceptionHTTP416 extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 416;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Requested Range Not Satisfiable';
}
