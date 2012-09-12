<?php

/**
 * Requests for PHP
 *
 * Inspired by Requests for Python.
 *
 * Based on concepts from SimplePie_File, RequestCore and WP_Http.
 *
 * @package Requests
 */

/**
 * Requests for PHP
 *
 * Inspired by Requests for Python.
 *
 * Based on concepts from SimplePie_File, RequestCore and WP_Http.
 *
 * @package Requests
 */
class Requests {
	/**
	 * POST method
	 *
	 * @var string
	 */
	const POST = 'POST';

	/**
	 * PUT method
	 *
	 * @var string
	 */
	const PUT = 'PUT';

	/**
	 * GET method
	 *
	 * @var string
	 */
	const GET = 'GET';

	/**
	 * HEAD method
	 *
	 * @var string
	 */
	const HEAD = 'HEAD';

	/**
	 * DELETE method
	 *
	 * @var string
	 */
	const DELETE = 'DELETE';

	/**
	 * PATCH method
	 *
	 * @link http://tools.ietf.org/html/rfc5789
	 * @var string
	 */
	const PATCH = 'PATCH';

	/**
	 * Current version of Requests
	 *
	 * @var string
	 */
	const VERSION = '1.6-dev';

	/**
	 * Registered transport classes
	 *
	 * @var array
	 */
	protected static $transports = array();

	/**
	 * Selected transport name
	 *
	 * Use {@see get_transport()} instead
	 *
	 * @var string|null
	 */
	public static $transport = null;

	/**
	 * This is a static class, do not instantiate it
	 *
	 * @codeCoverageIgnore
	 */
	private function __construct() {}

	/**
	 * Autoloader for Requests
	 *
	 * Register this with {@see register_autoloader()} if you'd like to avoid
	 * having to create your own.
	 *
	 * (You can also use `spl_autoload_register` directly if you'd prefer.)
	 *
	 * @codeCoverageIgnore
	 *
	 * @param string $class Class name to load
	 */
	public static function autoloader($class) {
		// Check that the class starts with "Requests"
		if (strpos($class, 'Requests') !== 0) {
			return;
		}

		$file = str_replace('_', '/', $class);
		if (file_exists(dirname(__FILE__) . '/' . $file . '.php')) {
			require_once(dirname(__FILE__) . '/' . $file . '.php');
		}
	}

	/**
	 * Register the built-in autoloader
	 *
	 * @codeCoverageIgnore
	 */
	public static function register_autoloader() {
		spl_autoload_register(array('Requests', 'autoloader'));
	}

	/**
	 * Register a transport
	 *
	 * @param string $transport Transport class to add, must support the RequestsTransport interface
	 */
	public static function add_transport($transport) {
		if (empty(self::$transports)) {
			self::$transports = array(
				'RequestsTransportcURL',
				'RequestsTransportfsockopen',
			);
		}

		self::$transports = array_merge(self::$transports, array($transport));
	}

	/**
	 * Get a working transport
	 *
	 * @throws RequestsException If no valid transport is found (`notransport`)
	 * @return RequestsTransport
	 */
	protected static function get_transport() {
		// Caching code, don't bother testing coverage
		// @codeCoverageIgnoreStart
		if (self::$transport !== null) {
			return new self::$transport();
		}
		// @codeCoverageIgnoreEnd

		if (empty(self::$transports)) {
			self::$transports = array(
				'RequestsTransportcURL',
				'RequestsTransportfsockopen',
			);
		}

		// Find us a working transport
		foreach (self::$transports as $class) {
			if (!class_exists($class))
				continue;

			$result = call_user_func(array($class, 'test'));
			if ($result) {
				self::$transport = $class;
				break;
			}
		}
		if (self::$transport === null) {
			throw new RequestsException('No working transports found', 'notransport', self::$transports);
		}

		return new self::$transport();
	}

	/**#@+
	 * @see request()
	 * @param string $url
	 * @param array $headers
	 * @param array $options
	 * @return RequestsResponse
	 */
	/**
	 * Send a GET request
	 */
	public static function get($url, $headers = array(), $options = array()) {
		return self::request($url, $headers, null, self::GET, $options);
	}

	/**
	 * Send a HEAD request
	 */
	public static function head($url, $headers = array(), $options = array()) {
		return self::request($url, $headers, null, self::HEAD, $options);
	}

	/**
	 * Send a DELETE request
	 */
	public static function delete($url, $headers = array(), $options = array()) {
		return self::request($url, $headers, null, self::DELETE, $options);
	}
	/**#@-*/

	/**#@+
	 * @see request()
	 * @param string $url
	 * @param array $headers
	 * @param array $data
	 * @param array $options
	 * @return RequestsResponse
	 */
	/**
	 * Send a POST request
	 */
	public static function post($url, $headers = array(), $data = array(), $options = array()) {
		return self::request($url, $headers, $data, self::POST, $options);
	}
	/**
	 * Send a PUT request
	 */
	public static function put($url, $headers = array(), $data = array(), $options = array()) {
		return self::request($url, $headers, $data, self::PUT, $options);
	}

	/**
	 * Send a PATCH request
	 *
	 * Note: Unlike {@see post} and {@see put}, `$headers` is required, as the
	 * specification recommends that should send an ETag
	 *
	 * @link http://tools.ietf.org/html/rfc5789
	 */
	public static function patch($url, $headers, $data = array(), $options = array()) {
		return self::request($url, $headers, $data, self::PATCH, $options);
	}
	/**#@-*/

	/**
	 * Main interface for HTTP requests
	 *
	 * This method initiates a request and sends it via a transport before
	 * parsing.
	 *
	 * The `$options` parameter takes an associative array with the following
	 * options:
	 *
	 * - `timeout`: How long should we wait for a response?
	 *    (integer, seconds, default: 10)
	 * - `useragent`: Useragent to send to the server
	 *    (string, default: php-requests/$version)
	 * - `follow_redirects`: Should we follow 3xx redirects?
	 *    (boolean, default: true)
	 * - `redirects`: How many times should we redirect before erroring?
	 *    (integer, default: 10)
	 * - `blocking`: Should we block processing on this request?
	 *    (boolean, default: true)
	 * - `filename`: File to stream the body to instead.
	 *    (string|boolean, default: false)
	 * - `auth`: Authentication handler or array of user/password details to use
	 *    for Basic authentication
	 *    (RequestsAuth|array|boolean, default: false)
	 * - `idn`: Enable IDN parsing
	 *    (boolean, default: true)
	 * - `transport`: Custom transport. Either a class name, or a
	 *    transport object. Defaults to the first working transport from
	 *    {@see getTransport()}
	 *    (string|RequestsTransport, default: {@see getTransport()})
	 *
	 * @throws RequestsException On invalid URLs (`nonhttp`)
	 *
	 * @param string $url URL to request
	 * @param array $headers Extra headers to send with the request
	 * @param array $data Data to send either as a query string for GET/HEAD requests, or in the body for POST requests
	 * @param string $type HTTP request type (use Requests constants)
	 * @param array $options Options for the request (see description for more information)
	 * @return RequestsResponse
	 */
	public static function request($url, $headers = array(), $data = array(), $type = self::GET, $options = array()) {
		if (!preg_match('/^http(s)?:\/\//i', $url)) {
			throw new RequestsException('Only HTTP requests are handled.', 'nonhttp', $url);
		}
		$defaults = array(
			'timeout' => 10,
			'useragent' => 'php-requests/' . self::VERSION,
			'redirected' => 0,
			'redirects' => 10,
			'follow_redirects' => true,
			'blocking' => true,
			'type' => $type,
			'filename' => false,
			'auth' => false,
			'idn' => true,
			'hooks' => null,
			'transport' => null,
		);
		$options = array_merge($defaults, $options);

		if (empty($options['hooks'])) {
			$options['hooks'] = new RequestsHooks();
		}

		// Special case for simple basic auth
		if (is_array($options['auth'])) {
			$options['auth'] = new RequestsAuthBasic($options['auth']);
		}
		if ($options['auth'] !== false) {
			$options['auth']->register($options['hooks']);
		}

		$options['hooks']->dispatch('requests.before_request', array(&$url, &$headers, &$data, &$type, &$options));

		if ($options['idn'] !== false) {
			$iri = new RequestsIRI($url);
			// Uses get magic method.
			$iri->host = RequestsIDNAEncoder::encode($iri->ihost);
			$url = $iri->uri;
		}

		if (!empty($options['transport'])) {
			$transport = $options['transport'];

			if (is_string($options['transport'])) {
				$transport = new $transport();
			}
		}
		else {
			$transport = self::get_transport();
		}
		$response = $transport->request($url, $headers, $data, $options);

		$options['hooks']->dispatch('requests.before_parse', array(&$response, $url, $headers, $data, $type, $options));

		return self::parse_response($response, $url, $headers, $data, $options);
	}

	/**
	 * HTTP response parser
	 *
	 * @throws RequestsException On missing head/body separator (`requests.no_crlf_separator`)
	 * @throws RequestsException On missing head/body separator (`noversion`)
	 * @throws RequestsException On missing head/body separator (`toomanyredirects`)
	 *
	 * @param string $headers Full response text including headers and body
	 * @param string $url Original request URL
	 * @param array $req_headers Original $headers array passed to {@link request()}, in case we need to follow redirects
	 * @param array $req_data Original $data array passed to {@link request()}, in case we need to follow redirects
	 * @param array $options Original $options array passed to {@link request()}, in case we need to follow redirects
	 * @return RequestsResponse
	 */
	protected static function parse_response($headers, $url, $req_headers, $req_data, $options) {
		$return = new RequestsResponse();
		if (!$options['blocking']) {
			return $return;
		}

		$return->raw = $headers;
		$return->url = $url;

		if (!$options['filename']) {
			if (strpos($headers, "\r\n\r\n") === false) {
				// Crap!
				throw new RequestsException('Missing header/body separator', 'requests.no_crlf_separator');
			}

			$headers = explode("\r\n\r\n", $headers, 2);
			$return->body = array_pop($headers);
			$headers = $headers[0];
		}
		else {
			$return->body = '';
		}
		// Pretend CRLF = LF for compatibility (RFC 2616, section 19.3)
		$headers = str_replace("\r\n", "\n", $headers);
		// Unfold headers (replace [CRLF] 1*( SP | HT ) with SP) as per RFC 2616 (section 2.2)
		$headers = preg_replace('/\n[ \t]/', ' ', $headers);
		$headers = explode("\n", $headers);
		preg_match('#^HTTP/1\.\d[ \t]+(\d+)#i', array_shift($headers), $matches);
		if (empty($matches)) {
			throw new RequestsException('Response could not be parsed', 'noversion', $headers);
		}
		$return->status_code = (int) $matches[1];
		if ($return->status_code >= 200 && $return->status_code < 300) {
			$return->success = true;
		}

		foreach ($headers as $header) {
			list($key, $value) = explode(':', $header, 2);
			$value = trim($value);
			preg_replace('#(\s+)#i', ' ', $value);
			$return->headers[$key] = $value;
		}
		if (isset($return->headers['transfer-encoding'])) {
			$return->body = self::decode_chunked($return->body);
			unset($return->headers['transfer-encoding']);
		}
		if (isset($return->headers['content-encoding'])) {
			$return->body = self::decompress($return->body);
		}

		//fsockopen and cURL compatibility
		if (isset($return->headers['connection'])) {
			unset($return->headers['connection']);
		}

		if ((in_array($return->status_code, array(300, 301, 302, 303, 307)) || $return->status_code > 307 && $return->status_code < 400) && $options['follow_redirects'] === true) {
			if (isset($return->headers['location']) && $options['redirected'] < $options['redirects']) {
				if ($return->status_code === 303) {
					$options['type'] = Requests::GET;
				}
				$options['redirected']++;
				$location = $return->headers['location'];
				if (strpos ($location, '/') === 0) {
					// relative redirect, for compatibility make it absolute
					$location = RequestsIRI::absolutize($url, $location);
				}
				$redirected = self::request($location, $req_headers, $req_data, false, $options);
				$redirected->history[] = $return;
				return $redirected;
			}
			elseif ($options['redirected'] >= $options['redirects']) {
				throw new RequestsException('Too many redirects', 'toomanyredirects', $return);
			}
		}

		$return->redirects = $options['redirected'];

		$options['hooks']->dispatch('requests.after_request', array(&$return, $req_headers, $req_data, $options));
		return $return;
	}

	/**
	 * Decoded a chunked body as per RFC 2616
	 *
	 * @see http://tools.ietf.org/html/rfc2616#section-3.6.1
	 * @param string $data Chunked body
	 * @return string Decoded body
	 */
	protected static function decode_chunked($data) {
		if (!preg_match('/^([0-9a-f]+)[^\r\n]*\r\n/i', trim($data))) {
			return $data;
		}

		$decoded = '';
		$encoded = $data;

		while (true) {
			$is_chunked = (bool) preg_match( '/^([0-9a-f]+)[^\r\n]*\r\n/i', $encoded, $matches );
			if (!$is_chunked) {
				// Looks like it's not chunked after all
				return $data;
			}

			$length = hexdec(trim($matches[1]));
			if ($length === 0) {
				// Ignore trailer headers
				return $decoded;
			}

			$chunk_length = strlen($matches[0]);
			$decoded .= $part = substr($encoded, $chunk_length, $length);
			$encoded = substr($encoded, $chunk_length + $length + 2);

			if (trim($encoded) === '0' || empty($encoded)) {
				return $decoded;
			}
		}

		// We'll never actually get down here
		// @codeCoverageIgnoreStart
	}
	// @codeCoverageIgnoreEnd

	/**
	 * Convert a key => value array to a 'key: value' array for headers
	 *
	 * @param array $array Dictionary of header values
	 * @return array List of headers
	 */
	public static function flattern($array) {
		$return = array();
		foreach ($array as $key => $value) {
			$return[] = "$key: $value";
		}
		return $return;
	}

	/**
	 * Decompress an encoded body
	 *
	 * Implements gzip, compress and deflate. Guesses which it is by attempting
	 * to decode.
	 *
	 * @todo Make this smarter by defaulting to whatever the headers say first
	 * @param string $data Compressed data in one of the above formats
	 * @return string Decompressed string
	 */
	protected static function decompress($data) {
		if (substr($data, 0, 2) !== "\x1f\x8b") {
			// Not actually compressed. Probably cURL ruining this for us.
			return $data;
		}

		if (function_exists('gzdecode') && ($decoded = gzdecode($data)) !== false) {
			return $decoded;
		}
		elseif (function_exists('gzinflate') && ($decoded = @gzinflate($data)) !== false) {
			return $decoded;
		}
		elseif (($decoded = self::compatible_gzinflate($data)) !== false) {
			return $decoded;
		}
		elseif (function_exists('gzuncompress') && ($decoded = @gzuncompress($data)) !== false) {
			return $decoded;
		}

		return $data;
	}

	/**
	 * Decompress deflated string while staying compatible with the majority of servers.
	 *
	 * Certain servers will return deflated data with headers which PHP's gziniflate()
	 * function cannot handle out of the box. The following function lifted from
	 * http://au2.php.net/manual/en/function.gzinflate.php#77336 will attempt to deflate
	 * the various return forms used.
	 *
	 * @link http://au2.php.net/manual/en/function.gzinflate.php#77336
	 *
	 * @param string $gzData String to decompress.
	 * @return string|bool False on failure.
	 */
	protected static function compatible_gzinflate($gzData) {
		if ( substr($gzData, 0, 3) == "\x1f\x8b\x08" ) {
			$i = 10;
			$flg = ord( substr($gzData, 3, 1) );
			if ( $flg > 0 ) {
				if ( $flg & 4 ) {
					list($xlen) = unpack('v', substr($gzData, $i, 2) );
					$i = $i + 2 + $xlen;
				}
				if ( $flg & 8 )
					$i = strpos($gzData, "\0", $i) + 1;
				if ( $flg & 16 )
					$i = strpos($gzData, "\0", $i) + 1;
				if ( $flg & 2 )
					$i = $i + 2;
			}
			return gzinflate( substr($gzData, $i, -8) );
		} else {
			return false;
		}
	}
}
