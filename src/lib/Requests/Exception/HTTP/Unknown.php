<?php
/**
 * Exception for unknown status responses
 *
 * @package Requests
 */

/**
 * Exception for unknown status responses
 *
 * @package Requests
 */
class RequestsExceptionHTTPUnknown extends RequestsExceptionHTTP {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 0;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Unknown';

	/**
	 * Create a new exception
	 *
	 * If `$data` is an instance of {@see Requests_Response}, uses the status
	 * code from it. Otherwise, sets as 0
	 *
	 * @param string $reason Reason phrase
	 * @param mixed $data Associated data
	 */
	public function __construct($reason = null, $data = null) {
		if ($data instanceof RequestsResponse) {
			$this->code = $data->status_code;
		}

		parent::__construct($reason, $data);
	}
}
