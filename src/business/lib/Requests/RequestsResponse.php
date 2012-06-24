<?php
namespace Blocks;

/**
 * HTTP response class
 *
 * Contains a response from Requests::request()
 * @package Requests
 */

/**
 * HTTP response class
 *
 * Contains a response from Requests::request()
 * @package Requests
 */
class RequestsResponse {
	/**
	 * Constructor
	 */
	function __construct() {
		$this->headers = new RequestsResponseHeaders();
	}

	/**
	 * Response body
	 * @var string
	 */
	public $body = '';

	/**
	 * Raw HTTP data from the transport
	 * @var string
	 */
	public $raw = '';

	/**
	 * Headers, as an associative array
	 * @var array
	 */
	public $headers = array();

	/**
	 * Status code, false if non-blocking
	 * @var integer|boolean
	 */
	public $status_code = false;

	/**
	 * Whether the request succeeded or not
	 * @var boolean
	 */
	public $success = false;

	/**
	 * Number of redirects the request used
	 * @var integer
	 */
	public $redirects = 0;

	/**
	 * URL requested
	 * @var string
	 */
	public $url = '';

	/**
	 * Previous requests (from redirects)
	 * @var array Array of RequestsResponse objects
	 */
	public $history = array();
}
