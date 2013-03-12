<?php

/**
 * fsockopen HTTP transport
 *
 * @package Requests
 * @subpackage Transport
 */

/**
 * fsockopen HTTP transport
 *
 * @package Requests
 * @subpackage Transport
 */
class RequestsTransportfsockopen implements RequestsTransport {
	/**
	 * Raw HTTP data
	 *
	 * @var string
	 */
	public $headers = '';

	/**
	 * Stream metadata
	 *
	 * @var array Associative array of properties, see {@see http://php.net/stream_get_meta_data}
	 */
	public $info;

	/**
	 * Perform a request
	 *
	 * @throws RequestsException On failure to connect to socket (`fsockopenerror`)
	 * @throws RequestsException On socket timeout (`timeout`)
	 *
	 * @param string $url URL to request
	 * @param array $headers Associative array of request headers
	 * @param string|array $data Data to send either as the POST body, or as parameters in the URL for a GET/HEAD
	 * @param array $options Request options, see {@see Requests::response()} for documentation
	 * @return string Raw HTTP result
	 */
	public function request($url, $headers = array(), $data = array(), $options = array()) {
		$options['hooks']->dispatch('fsockopen.before_request');

		$url_parts = parse_url($url);
		$host = $url_parts['host'];
		if (isset($url_parts['scheme']) && strtolower($url_parts['scheme']) === 'https') {
			$host = 'ssl://' . $host;
			$url_parts['port'] = 443;
		}
		if (!isset($url_parts['port'])) {
			$url_parts['port'] = 80;
		}
		$fp = @fsockopen($host, $url_parts['port'], $errno, $errstr, $options['timeout']);
		if (!$fp) {
			throw new RequestsException($errstr, 'fsockopenerror');
			return;
		}

		$request_body = '';
		$out = '';
		switch ($options['type']) {
			case Requests::POST:
			case Requests::PUT:
			case Requests::PATCH:
				if (isset($url_parts['path'])) {
					$path = $url_parts['path'];
					if (isset($url_parts['query'])) {
						$path .= '?' . $url_parts['query'];
					}
				}
				else {
					$path = '/';
				}
				$out = $options['type'] . " $path HTTP/1.0\r\n";
				if (is_array($data)) {
					$request_body = http_build_query($data, null, '&');
				}
				else {
					$request_body = $data;
				}
				if (empty($headers['Content-Length'])) {
					$headers['Content-Length'] = strlen($request_body);
				}
				if (empty($headers['Content-Type'])) {
					$headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
				}
				break;
			case Requests::HEAD:
			case Requests::GET:
			case Requests::DELETE:
				$get = self::format_get($url_parts, $data);
				$out = $options['type'] . " $get HTTP/1.0\r\n";
				break;
		}
		$out .= "Host: {$url_parts['host']}\r\n";
		$out .= "User-Agent: {$options['useragent']}\r\n";
		$accept_encoding = $this->accept_encoding();
		if (!empty($accept_encoding)) {
			$out .= "Accept-Encoding: $accept_encoding\r\n";
		}

		$headers = Requests::flattern($headers);
		$out .= implode($headers, "\r\n");

		$options['hooks']->dispatch('fsockopen.after_headers', array(&$out));

		$out .= "\r\nConnection: Close\r\n\r\n" . $request_body;

		$options['hooks']->dispatch('fsockopen.before_send', array(&$out));

		fwrite($fp, $out);
		$options['hooks']->dispatch('fsockopen.after_send', array(&$fake_headers));

		if (!$options['blocking']) {
			fclose($fp);
			$fake_headers = '';
			$options['hooks']->dispatch('fsockopen.after_request', array(&$fake_headers));
			return '';
		}
		stream_set_timeout($fp, $options['timeout']);

		$this->info = stream_get_meta_data($fp);

		$this->headers = '';
		$this->info = stream_get_meta_data($fp);
		if (!$options['filename']) {
			while (!feof($fp)) {
				$this->info = stream_get_meta_data($fp);
				if ($this->info['timed_out']) {
					throw new RequestsException('fsocket timed out', 'timeout');
				}

				$this->headers .= fread($fp, 1160);
			}
		}
		else {
			$download = fopen($options['filename'], 'wb');
			$doingbody = false;
			$response = '';
			while (!feof($fp)) {
				$this->info = stream_get_meta_data($fp);
				if ($this->info['timed_out']) {
					throw new RequestsException('fsocket timed out', 'timeout');
				}

				$block = fread($fp, 1160);
				if ($doingbody) {
					fwrite($download, $block);
				}
				else {
					$response .= $block;
					if (strpos($response, "\r\n\r\n")) {
						list($this->headers, $block) = explode("\r\n\r\n", $response, 2);
						$doingbody = true;
						fwrite($download, $block);
					}
				}
			}
			fclose($download);
		}
		fclose($fp);

		$options['hooks']->dispatch('fsockopen.after_request', array(&$this->headers));
		return $this->headers;
	}

	/**
	 * Retrieve the encodings we can accept
	 *
	 * @return string Accept-Encoding header value
	 */
	protected static function accept_encoding() {
		$type = array();
		if (function_exists('gzinflate')) {
			$type[] = 'deflate;q=1.0';
		}

		if (function_exists('gzuncompress')) {
			$type[] = 'compress;q=0.5';
		}

		$type[] = 'gzip;q=0.5';

		return implode(', ', $type);
	}

	/**
	 * Format a URL given GET data
	 *
	 * @param array $url_parts
	 * @param array|object $data Data to build query using, see {@see http://php.net/http_build_query}
	 * @return string URL with data
	 */
	protected static function format_get($url_parts, $data) {
		if (!empty($data)) {
			if (empty($url_parts['query']))
				$url_parts['query'] = '';

			$url_parts['query'] .= '&' . http_build_query($data, null, '&');
			$url_parts['query'] = trim($url_parts['query'], '&');
		}
		if (isset($url_parts['path'])) {
			if (isset($url_parts['query'])) {
				$get = "$url_parts[path]?$url_parts[query]";
			}
			else {
				$get = $url_parts['path'];
			}
		}
		else {
			$get = '/';
		}
		return $get;
	}

	/**
	 * Whether this transport is valid
	 *
	 * @codeCoverageIgnore
	 * @return boolean True if the transport is valid, false otherwise.
	 */
	public static function test() {
		return function_exists('fsockopen');
	}
}
