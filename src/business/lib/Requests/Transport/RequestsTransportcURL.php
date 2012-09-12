<?php

/**
 * cURL HTTP transport
 *
 * @package Requests
 * @subpackage Transport
 */

/**
 * cURL HTTP transport
 *
 * @package Requests
 * @subpackage Transport
 */
class RequestsTransportcURL implements RequestsTransport {
	/**
	 * Raw HTTP data
	 *
	 * @var string
	 */
	public $headers = '';

	/**
	 * Information on the current request
	 *
	 * @var array cURL information array, see {@see http://php.net/curl_getinfo}
	 */
	public $info;

	/**
	 * Version string
	 *
	 * @var string
	 */
	public $version;

	/**
	 * cURL handle
	 *
	 * @var resource
	 */
	protected $fp;

	/**
	 * Have we finished the headers yet?
	 *
	 * @var boolean
	 */
	protected $done_headers = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		$curl = curl_version();
		$this->version = $curl['version'];
		$this->fp = curl_init();

		curl_setopt($this->fp, CURLOPT_HEADER, false);
		curl_setopt($this->fp, CURLOPT_RETURNTRANSFER, 1);
		if (version_compare($this->version, '7.10.5', '>=')) {
			curl_setopt($this->fp, CURLOPT_ENCODING, '');
		}
		curl_setopt ($this->fp, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($this->fp, CURLOPT_SSL_VERIFYPEER, 0);
	}

	/**
	 * Perform a request
	 *
	 * @throws RequestsException On a cURL error (`curlerror`)
	 *
	 * @param string $url URL to request
	 * @param array $headers Associative array of request headers
	 * @param string|array $data Data to send either as the POST body, or as parameters in the URL for a GET/HEAD
	 * @param array $options Request options, see {@see Requests::response()} for documentation
	 * @return string Raw HTTP result
	 */
	public function request($url, $headers = array(), $data = array(), $options = array()) {
		$options['hooks']->dispatch('curl.before_request', array(&$this->fp));

		$headers = Requests::flattern($headers);
		if (in_array($options['type'], array(Requests::HEAD, Requests::GET, Requests::DELETE)) & !empty($data)) {
			$url = self::format_get($url, $data);
		}

		switch ($options['type']) {
			case Requests::POST:
				curl_setopt($this->fp, CURLOPT_POST, true);
				curl_setopt($this->fp, CURLOPT_POSTFIELDS, $data);
				break;
			case Requests::PATCH:
			case Requests::PUT:
				curl_setopt($this->fp, CURLOPT_CUSTOMREQUEST, $options['type']);
				curl_setopt($this->fp, CURLOPT_POSTFIELDS, $data);
				break;
			case Requests::DELETE:
				curl_setopt($this->fp, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
			case Requests::HEAD:
				curl_setopt($this->fp, CURLOPT_NOBODY, true);
				break;
		}

		curl_setopt($this->fp, CURLOPT_URL, $url);
		curl_setopt($this->fp, CURLOPT_TIMEOUT, $options['timeout']);
		curl_setopt($this->fp, CURLOPT_CONNECTTIMEOUT, $options['timeout']);
		curl_setopt($this->fp, CURLOPT_REFERER, $url);
		curl_setopt($this->fp, CURLOPT_USERAGENT, $options['useragent']);
		curl_setopt($this->fp, CURLOPT_HTTPHEADER, $headers);

		if (true === $options['blocking']) {
			curl_setopt($this->fp, CURLOPT_HEADERFUNCTION, array(&$this, 'stream_headers'));
		}

		$options['hooks']->dispatch('curl.before_send', array(&$this->fp));

		if ($options['filename'] !== false) {
			$stream_handle = fopen($options['filename'], 'wb');
			curl_setopt($this->fp, CURLOPT_FILE, $stream_handle);
		}

		$response = curl_exec($this->fp);

		$options['hooks']->dispatch('curl.after_send', array(&$fake_headers));

		if ($options['blocking'] === false) {
			curl_close($this->fp);
			$fake_headers = '';
			$options['hooks']->dispatch('curl.after_request', array(&$fake_headers));
			return false;
		}
		if ($options['filename'] !== false) {
			fclose($stream_handle);
			$this->headers = trim($this->headers);
		}
		else {
			$this->headers .= $response;
		}

		if (curl_errno($this->fp) === 23 || curl_errno($this->fp) === 61) {
			curl_setopt($this->fp, CURLOPT_ENCODING, 'none');
			$this->headers = curl_exec($this->fp);
		}
		if (curl_errno($this->fp)) {
			throw new RequestsException('cURL error ' . curl_errno($this->fp) . ': ' . curl_error($this->fp), 'curlerror', $this->fp);
			return;
		}
		$this->info = curl_getinfo($this->fp);
		curl_close($this->fp);

		$options['hooks']->dispatch('curl.after_request', array(&$this->headers));
		return $this->headers;
	}

	/**
	 * Collect the headers as they are received
	 *
	 * @param resource $handle cURL resource
	 * @param string $headers Header string
	 * @return integer Length of provided header
	 */
	protected function stream_headers($handle, $headers) {
		// Why do we do this? cURL will send both the final response and any
		// interim responses, such as a 100 Continue. We don't need that.
		// (We may want to keep this somewhere just in case)
		if ($this->done_headers) {
			$this->headers = '';
			$this->done_headers = false;
		}
		$this->headers .= $headers;

		if ($headers === "\r\n") {
			$this->done_headers = true;
		}
		return strlen($headers);
	}

	/**
	 * Format a URL given GET data
	 *
	 * @param string $url
	 * @param array|object $data Data to build query using, see {@see http://php.net/http_build_query}
	 * @return string URL with data
	 */
	protected static function format_get($url, $data) {
		if (!empty($data)) {
			$url_parts = parse_url($url);
			if (empty($url_parts['query'])) {
				$query = $url_parts['query'] = '';
			}
			else {
				$query = $url_parts['query'];
			}

			$query .= '&' . http_build_query($data, null, '&');
			$query = trim($query, '&');

			if (empty($url_parts['query'])) {
				$url .= '?' . $query;
			}
			else {
				$url = str_replace($url_parts['query'], $query, $url);
			}
		}
		return $url;
	}

	/**
	 * Whether this transport is valid
	 *
	 * @codeCoverageIgnore
	 * @return boolean True if the transport is valid, false otherwise.
	 */
	public static function test() {
		return (function_exists('curl_init') && function_exists('curl_exec'));
	}
}
