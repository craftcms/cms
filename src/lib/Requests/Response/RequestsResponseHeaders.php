<?php

/**
 * Case-insensitive dictionary, suitable for HTTP headers
 *
 * @package Requests
 */

/**
 * Case-insensitive dictionary, suitable for HTTP headers
 *
 * @package Requests
 */
class RequestsResponseHeaders implements ArrayAccess, IteratorAggregate {
	/**
	 * Actual header data
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Check if the given header exists
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function offsetExists($key) {
		$key = strtolower($key);
		return isset($this->data[$key]);
	}

	/**
	 * Get the given header
	 *
	 * @param string $key
	 * @return string Header value
	 */
	public function offsetGet($key) {
		$key = strtolower($key);
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	/**
	 * Set the given header
	 *
	 * @throws RequestsException On attempting to use headers dictionary as list (`invalidset`)
	 *
	 * @param string $key Header name
	 * @param string $value Header value
	 */
	public function offsetSet($key, $value) {
		if ($key === null) {
			throw new RequestsException('Headers is a dictionary, not a list', 'invalidset');
		}

		$key = strtolower($key);

		if (isset($this->data[$key])) {
			// RFC2616 notes that multiple headers must be able to
			// be combined like this. We should use a smarter way though (arrays
			// internally, e.g.)
			$value = $this->data[$key] . ',' . $value;
		}

		$this->data[$key] = $value;
	}

	/**
	 * Unset the given header
	 *
	 * @param string $key
	 */
	public function offsetUnset($key) {
		unset($this->data[strtolower($key)]);
	}

	/**
	 * Get an iterator for the data
	 *
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator($this->data);
	}
}
