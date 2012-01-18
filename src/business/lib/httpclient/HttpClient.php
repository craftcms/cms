<?php

/**
 * Modified version of Zend_Http_Client of Zend
 *
 * Copyright (c) 2005-2010, Zend Technologies USA, Inc.
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * 
 *     * Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *     * Redistributions in binary form must reproduce the above copyright notice,
 *       this list of conditions and the following disclaimer in the documentation
 *       and/or other materials provided with the distribution.
 * 
 *     * Neither the name of Zend Technologies USA, Inc. nor the names of its
 *       contributors may be used to endorse or promote products derived from this
 *       software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * HttpClient is an implementation of an HTTP client in PHP. The client
 * supports basic features like sending different HTTP requests and handling
 * redirections, as well as more advanced features like proxy settings, HTTP
 * authentication and cookie persistence (using a HttpCookieJar object)
 */


class HttpClient
{
	/**
	 * HTTP request methods
	 */
	const GET     = 'GET';
	const POST    = 'POST';
	const PUT     = 'PUT';
	const HEAD    = 'HEAD';
	const DELETE  = 'DELETE';
	const TRACE   = 'TRACE';
	const OPTIONS = 'OPTIONS';
	const CONNECT = 'CONNECT';
	const MERGE   = 'MERGE';

	/**
	 * Supported HTTP Authentication methods
	 */
	const AUTH_BASIC = 'basic';
	//const AUTH_DIGEST = 'digest'; <-- not implemented yet

	/**
	 * HTTP protocol versions
	 */
	const HTTP_1 = '1.1';
	const HTTP_0 = '1.0';
	/**
	 * Content attributes
	 */
	const CONTENT_TYPE   = 'Content-Type';
	const CONTENT_LENGTH = 'Content-Length';

	/**
	 * POST data encoding methods
	 */
	const ENC_URLENCODED = 'application/x-www-form-urlencoded';
	const ENC_FORMDATA   = 'multipart/form-data';

	/**
	 * Configuration array, set using the constructor or using ::setConfig()
	 * @var array
	 */
	protected $config = array(
		'maxredirects'    => 5,
		'strictredirects' => false,
		'useragent'       => 'HttpClient',
		'timeout'         => 10,
		'adapter'         => 'HttpClientAdapterSocket',
		'httpversion'     => self::HTTP_1,
		'keepalive'       => false,
		'storeresponse'   => true,
		'strict'          => true,
		'output_stream'   => false,
		'encodecookies'   => true,
		'rfc3986_strict'  => false
	);

	private $_streamName = null;

	/**
	 * The adapter used to preform the actual connection to the server
	 * @var HttpClientAdapterInterface
	 */
	protected $adapter = null;

	/**
	 * Request URI
	 * @var UriHttp
	 */
	protected $uri = null;

	/**
	 * Associative array of request headers
	 * @var array
	 */
	protected $headers = array();

	/**
	 * HTTP request method
	 * @var string
	 */
	protected $method = self::GET;

	/**
	 * Associative array of GET parameters
	 * @var array
	 */
	protected $paramsGet = array();

	/**
	 * Associative array of POST parameters
	 * @var array
	 */
	protected $paramsPost = array();

	/**
	 * Request body content type (for POST requests)
	 * @var string
	 */
	protected $encType = null;

	/**
	 * The raw post data to send. Could be set by setRawData($data, $encType).
	 * @var string
	 */
	protected $rawPostData = null;

	/**
	 * HTTP Authentication settings
	 * Expected to be an associative array with this structure:
	 * $this->auth = array('user' => 'username', 'password' => 'password', 'type' => 'basic')
	 * Where 'type' should be one of the supported authentication types (see the AUTH_*
	 * constants), for example 'basic' or 'digest'.
	 * If null, no authentication will be used.
	 * @var array|null
	 */
	protected $auth;

	/**
	 * File upload arrays (used in POST requests)
	 * An associative array, where each element is of the format: 'name' => array('filename.txt', 'text/plain', 'This is the actual file contents')
	 * @var array
	 */
	protected $files = array();

	/**
	 * The client's cookie jar
	 * @var CookieJar
	 */
	protected $cookieJar = null;

	/**
	 * The last HTTP request sent by the client, as string
	 * @var string
	 */
	protected $lastRequest = null;

	/**
	 * The last HTTP response received by the client
	 * @var HttpResponse
	 */
	protected $lastResponse = null;

	/**
	 * Redirection counter
	 * @var int
	 */
	protected $redirectCounter = 0;

	/**
	 * FileInfo magic database resource
	 * This variable is populated the first time _detectFileMimeType is called
	 * and is then reused on every call to this method
	 * @var resource
	 */
	static protected $_fileInfoDb = null;

	/**
	 * Constructor method. Will create a new HTTP client. Accepts the target
	 * URL and optionally configuration array.
	 * @param UriHttp|string $uri
	 * @param array $config Configuration key-value pairs.
	 */
	public function __construct($uri = null, $config = null)
	{
		if ($uri !== null)
		{
			$this->setUri($uri);
		}

		if ($config !== null)
		{
			$this->setConfig($config);
		}
	}

	/**
	 * Set the URI for the next request
	 * @param  UriHttp|string $uri
	 * @return HttpClient
	 * @throws bException
	 */
	public function setUri($uri)
	{
		if (is_string($uri))
		{
			$uri = Uri::factory($uri);
		}

		if (!$uri instanceof UriHttp)
		{
			throw new bException('Passed parameter is not a valid HTTP URI.');
		}

		// Set auth if username and password has been specified in the uri
		if ($uri->getUsername() && $uri->getPassword()) {
			$this->setAuth($uri->getUsername(), $uri->getPassword());
		}
		// We have no ports, set the defaults
		if (! $uri->getPort())
		{
			$uri->setPort(($uri->getScheme() == 'https' ? 443 : 80));
		}

		$this->uri = $uri;

		return $this;
	}

	/**
	 * Get the URI for the next request
	 * @param boolean $as_string If true, will return the URI as a string
	 * @return UriHttp|string
	 */
	public function getUri($as_string = false)
	{
		if ($as_string && $this->uri instanceof UriHttp)
		{
			return $this->uri->__toString();
		}
		else
		{
			return $this->uri;
		}
	}

	/**
	 * Set configuration parameters for this HTTP client
	 * @param array $config
	 * @return HttpClient
	 * @throws bException
	 */
	public function setConfig($config = array())
	{
		if (!is_array($config))
		{
			throw new bException('Expected array parameter, given '.gettype($config));
		}

		foreach ($config as $k => $v)
			$this->config[strtolower($k)] = $v;

		return $this;
	}

	/**
	 * Set the next request's method
	 * Validated the passed method and sets it. If we have files set for POST requests, and the new method is not POST, the files are silently dropped.
	 * @param string $method
	 * @return HttpClient
	 * @throws bException
	 */
	public function setMethod($method = self::GET)
	{
		if (!preg_match('/^[^\x00-\x1f\x7f-\xff\(\)<>@,;:\\\\"\/\[\]\?={}\s]+$/', $method))
		{
			throw new bException("'{$method}' is not a valid HTTP request method.");
		}

		if ($method == self::POST && $this->encType === null)
		{
			$this->setEncType(self::ENC_URLENCODED);
		}

		$this->method = $method;

		return $this;
	}

	/**
	 * Set one or more request headers
	 * This function can be used in several ways to set the client's request
	 * headers:
	 * 1. By providing two parameters: $name as the header to set (eg. 'Host')
	 *    and $value as it's value (eg. 'www.example.com').
	 * 2. By providing a single header string as the only parameter
	 *    eg. 'Host: www.example.com'
	 * 3. By providing an array of headers as the first parameter
	 *    eg. array('host' => 'www.example.com', 'x-foo: bar'). In This case
	 *    the function will call itself recursively for each array item.
	 * @param string|array $name Header name, full header string ('Header: value') or an array of headers
	 * @param mixed $value Header value or null
	 * @return HttpClient
	 * @throws bException
	 */
	public function setHeaders($name, $value = null)
	{
		// If we got an array, go recursive
		if (is_array($name))
		{
			foreach ($name as $k => $v)
			{
				if (is_string($k))
				{
					$this->setHeaders($k, $v);
				}
				else
				{
					$this->setHeaders($v, null);
				}
			}
		}
		else
		{
			// Check if $name needs to be split
			if ($value === null && (strpos($name, ':') > 0))
				list($name, $value) = explode(':', $name, 2);

			// Make sure the name is valid if we are in strict mode
			if ($this->config['strict'] && (!preg_match('/^[a-zA-Z0-9-]+$/', $name)))
			{
				throw new bException("{$name} is not a valid HTTP header name");
			}

			$normalized_name = strtolower($name);

			// If $value is null or false, unset the header
			if ($value === null || $value === false)
			{
				unset($this->headers[$normalized_name]);

			// else, set the header
			}
			else
			{
				// Header names are stored lowercase internally.
				if (is_string($value))
				{
					$value = trim($value);
				}

				$this->headers[$normalized_name] = array($name, $value);
			}
		}

		return $this;
	}

	/**
	 * Get the value of a specific header
	 * Note that if the header has more than one value, an array will be returned.
	 * @param string $key
	 * @return string|array|null The header value or null if it is not set
	 */
	public function getHeader($key)
	{
		$key = strtolower($key);

		if (isset($this->headers[$key]))
		{
			return $this->headers[$key][1];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Set a GET parameter for the request. Wrapper around _setParameter
	 * @param string|array $name
	 * @param string $value
	 * @return HttpClient
	 */
	public function setParameterGet($name, $value = null)
	{
		if (is_array($name))
		{
			foreach ($name as $k => $v)
			{
				$this->_setParameter('GET', $k, $v);
			}
		}
		else
		{
			$this->_setParameter('GET', $name, $value);
		}

		return $this;
	}

	/**
	 * Set a POST parameter for the request. Wrapper around _setParameter
	 * @param string|array $name
	 * @param string $value
	 * @return HttpClient
	 */
	public function setParameterPost($name, $value = null)
	{
		if (is_array($name))
		{
			foreach ($name as $k => $v)
			{
				$this->_setParameter('POST', $k, $v);
			}
		}
		else
		{
			$this->_setParameter('POST', $name, $value);
		}

		return $this;
	}

	/**
	 * Set a GET or POST parameter - used by SetParameterGet and SetParameterPost
	 * @param string $type GET or POST
	 * @param string $name
	 * @param string $value
	 * @return null
	 */
	protected function _setParameter($type, $name, $value)
	{
		$pArray = array();
		$type = strtolower($type);

		switch ($type)
		{
			case 'get':
				$pArray = &$this->paramsGet;
				break;
			case 'post':
				$pArray = &$this->paramsPost;
				break;
		}

		if ($value === null)
		{
			if (isset($pArray[$name]))
			{
				unset($pArray[$name]);
			}
		}
		else
		{
			$pArray[$name] = $value;
		}
	}

	/**
	 * Get the number of redirections done on the last request
	 * @return int
	 */
	public function getRedirectionsCount()
	{
		return $this->redirectCounter;
	}

	/**
	 * Set HTTP authentication parameters
	 * $type should be one of the supported types - see the self::AUTH_* constants.
	 * To enable authentication:
	 * <code>
	 * $this->setAuth('shahar', 'secret', HttpClient::AUTH_BASIC);
	 * </code>
	 * To disable authentication:
	 * <code>
	 * $this->setAuth(false);
	 * </code>
	 * @see http://www.faqs.org/rfcs/rfc2617.html
	 * @param string|false $user User name or false disable authentication
	 * @param string $password Password
	 * @param string $type Authentication type
	 * @return HttpClient
	 * @throws bException
	 */
	public function setAuth($user, $password = '', $type = self::AUTH_BASIC)
	{
		// If we got false or null, disable authentication
		if ($user === false || $user === null)
		{
			$this->auth = null;

			// Clear the auth information in the uri instance as well
			if ($this->uri instanceof UriHttp)
			{
				$this->getUri()->setUsername('');
				$this->getUri()->setPassword('');
			}
		// else, set up authentication
		}
		else
		{
			// Check we got a proper authentication type
			if (!defined('self::AUTH_'.strtoupper($type)))
			{
				throw new bException("Invalid or not supported authentication type: '$type'");
			}

			$this->auth = array(
				'user'      => (string)$user,
				'password'  => (string)$password,
				'type'      => $type
			);
		}

		return $this;
	}

	/**
	 * Set the HTTP client's cookie jar.
	 * A cookie jar is an object that holds and maintains cookies across HTTP requests and responses.
	 * @param HttpCookieJar|boolean $cookieJar Existing CookieJar object, true to create a new one, false to disable
	 * @return HttpClient
	 * @throws bException
	 */
	public function setCookieJar($cookieJar = true)
	{
		if (!class_exists('CookieJar'))
			require_once 'CookieJar.php';

		if ($cookieJar instanceof CookieJar)
		{
			$this->cookieJar = $cookieJar;
		}
		else
		{
			if ($cookieJar === true)
			{
				$this->cookieJar = new CookieJar();
			}
			else
			{
				if (!$cookieJar)
				{
					$this->cookieJar = null;
				}
				else
				{
					throw new bException('Invalid parameter type passed as CookieJar');
				}
			}
		}

		return $this;
	}

	/**
	 * Return the current cookie jar or null if none.
	 * @return HttpCookieJar|null
	 */
	public function getCookieJar()
	{
		return $this->cookieJar;
	}

	/**
	 * Add a cookie to the request. If the client has no Cookie Jar, the cookies
	 * will be added directly to the headers array as "Cookie" headers.
	 * @param HttpCookie|string $cookie
	 * @param string|null $value If "cookie" is a string, this is the cookie value.
	 * @return HttpClient
	 * @throws bException
	 */
	public function setCookie($cookie, $value = null)
	{
		if (!class_exists('HttpCookie'))
			require_once 'HttpCookie.php';

		if (is_array($cookie))
		{
			foreach ($cookie as $c => $v)
			{
				if (is_string($c))
				{
					$this->setCookie($c, $v);
				}
				else
				{
					$this->setCookie($v);
				}
			}

			return $this;
		}

		if ($value !== null && $this->config['encodecookies'])
		{
			$value = urlencode($value);
		}

		if (isset($this->cookieJar))
		{
			if ($cookie instanceof HttpCookie)
			{
				$this->cookieJar->addCookie($cookie);
			}
			else
			{
				if (is_string($cookie) && $value !== null)
				{
					$cookie = HttpCookie::fromString("{$cookie}={$value}", $this->uri, $this->config['encodecookies']);
					$this->cookieJar->addCookie($cookie);
				}
			}
		}
		else
		{
			if ($cookie instanceof HttpCookie)
			{
				$name = $cookie->getName();
				$value = $cookie->getValue();
				$cookie = $name;
			}

			if (preg_match("/[=,; \t\r\n\013\014]/", $cookie))
			{
				throw new bException("Cookie name cannot contain these characters: =,; \t\r\n\013\014 ({$cookie})");
			}

			$value = addslashes($value);

			if (!isset($this->headers['cookie']))
			{
				$this->headers['cookie'] = array('Cookie', '');
			}

			$this->headers['cookie'][1] .= $cookie.'='.$value.'; ';
		}

		return $this;
	}

	/**
	 * Set a file to upload (using a POST request)
	 * Can be used in two ways:
	 * 1. $data is null (default): $filename is treated as the name if a local file which
	 *    will be read and sent. Will try to guess the content type using mime_content_type().
	 * 2. $data is set - $filename is sent as the file name, but $data is sent as the file
	 *    contents and no file is read from the file system. In this case, you need to
	 *    manually set the content-type ($cType) or it will default to
	 *    application/octet-stream.
	 * @param string $fileName Name of file to upload, or name to save as
	 * @param string $formName Name of form element to send as
	 * @param string $data Data to send (if null, $filename is read and sent)
	 * @param string $cType Content type to use (if $data is set and $cType is null, will be application/octet-stream)
	 * @return HttpClient
	 * @throws bException
	 */
	public function setFileUpload($fileName, $formName, $data = null, $cType = null)
	{
		if ($data === null)
		{
			if (($data = @file_get_contents($fileName)) === false)
			{
				throw new bException("Unable to read file '{$fileName}' for upload");
			}

			if (!$cType)
			{
				$cType = $this->_detectFileMimeType($fileName);
			}
		}

		// Force encType to multipart/form-data
		$this->setEncType(self::ENC_FORMDATA);

		$this->files[] = array(
			'formname' => $formName,
			'filename' => basename($fileName),
			'ctype'    => $cType,
			'data'     => $data
		);

		return $this;
	}

	/**
	 * Set the encoding type for POST data
	 * @param string $encType
	 * @return HttpClient
	 */
	public function setEncType($encType = self::ENC_URLENCODED)
	{
		$this->encType = $encType;
		return $this;
	}

	/**
	 * Set the raw (already encoded) POST data.
	 * This function is here for two reasons:
	 * 1. For advanced user who would like to set their own data, already encoded
	 * 2. For backwards compatibility: If someone uses the old post($data) method.
	 *    this method will be used to set the encoded data.
	 * $data can also be stream (such as file) from which the data will be read.
	 * @param string|resource $data
	 * @param string $encType
	 * @return HttpClient
	 */
	public function setRawData($data, $encType = null)
	{
		$this->rawPostData = $data;
		$this->setEncType($encType);

		if (is_resource($data))
		{
			// We've got stream data
			$stat = @fstat($data);

			if($stat)
			{
				$this->setHeaders(self::CONTENT_LENGTH, $stat['size']);
			}
		}

		return $this;
	}

	/**
	 * Clear all GET and POST parameters
	 * Should be used to reset the request parameters if the client is used for several concurrent requests.
	 * clearAll parameter controls if we clean just parameters or also headers and last_*
	 * @param bool $clearAll Should all data be cleared?
	 * @return HttpClient
	 */
	public function resetParameters($clearAll = false)
	{
		// Reset parameter data
		$this->paramsGet = array();
		$this->paramsPost = array();
		$this->files  = array();
		$this->rawPostData = null;

		if($clearAll)
		{
			$this->headers = array();
			$this->lastRequest = null;
			$this->lastResponse = null;
		}
		else
		{
			// Clear outdated headers
			if (isset($this->headers[strtolower(self::CONTENT_TYPE)]))
			{
				unset($this->headers[strtolower(self::CONTENT_TYPE)]);
			}

			if (isset($this->headers[strtolower(self::CONTENT_LENGTH)]))
			{
				unset($this->headers[strtolower(self::CONTENT_LENGTH)]);
			}
		}

		return $this;
	}

	/**
	 * Get the last HTTP request as string
	 * @return string
	 */
	public function getLastRequest()
	{
		return $this->lastRequest;
	}

	/**
	 * Get the last HTTP response received by this client
	 * If $config['storeresponse'] is set to false, or no response was
	 * stored yet, will return null
	 * @return HttpResponse or null if none
	 */
	public function getLastResponse()
	{
		return $this->lastResponse;
	}

	/**
	 * Load the connection adapter
	 * While this method is not called more than one for a client, it is separated from ->request() to preserve logic and readability
	 * @param HttpClientAdapterInterface $adapter
	 * @return null
	 * @throws bException
	 */
	public function setAdapter($adapter)
	{
		$adapter = new $adapter;

		if (!$adapter instanceof HttpClientAdapterInterface)
		{
			throw new bException('Passed adapter is not a HTTP connection adapter');
		}

		$this->adapter = $adapter;
		$config = $this->config;
		unset($config['adapter']);
		$this->adapter->setConfig($config);
	}

	/**
	 * Load the connection adapter
	 * @return HttpClientAdapterInterface $adapter
	 */
	public function getAdapter()
	{
		return $this->adapter;
	}

	/**
	 * Set streaming for received data
	 * @param string|boolean $streamFile Stream file, true for temp file, false/null for no streaming
	 * @return HttpClient
	 */
	public function setStream($streamFile = true)
	{
		$this->setConfig(array("output_stream" => $streamFile));
		return $this;
	}

	/**
	 * Get status of streaming for received data
	 * @return boolean|string
	 */
	public function getStream()
	{
		return $this->config["output_stream"];
	}

	/**
	 * Create temporary stream
	 * @return resource
	 */
	protected function _openTempStream()
	{
		$this->_streamName = $this->config['output_stream'];

		if(!is_string($this->_streamName))
		{
			// If name is not given, create temp name
			$this->_streamName = tempnam(isset($this->config['stream_tmp_dir']) ? $this->config['stream_tmp_dir'] : sys_get_temp_dir(), 'HttpClient');
		}

		if (false === ($fp = @fopen($this->_streamName, "w+b")))
		{
				if ($this->adapter instanceof HttpClientAdapterInterface)
				{
					$this->adapter->close();
				}

				throw new bException("Could not open temp file {$this->_streamName}");
		}

		return $fp;
	}

	/**
	 * Send the HTTP request and return an HTTP response object
	 * @param string $method
	 * @return HttpResponse
	 * @throws bException
	 */
	public function request($method = null)
	{
		if (!$this->uri instanceof UriHttp)
		{
			throw new bException('No valid URI has been passed to the client');
		}

		if ($method)
			$this->setMethod($method);

		$this->redirectCounter = 0;
		$response = null;

		// Make sure the adapter is loaded
		if ($this->adapter == null)
			$this->setAdapter($this->config['adapter']);

		// Send the first request. If redirected, continue.
		do
		{
			// Clone the URI and add the additional GET parameters to it
			$uri = clone $this->uri;
			if (! empty($this->paramsGet))
			{
				$query = $uri->getQuery();

				if (!empty($query))
				{
					$query .= '&';
				}

				$query .= http_build_query($this->paramsGet, null, '&');
				if ($this->config['rfc3986_strict'])
				{
					$query = str_replace('+', '%20', $query);
				}

				$uri->setQuery($query);
			}

			$body = $this->_prepareBody();
			$headers = $this->_prepareHeaders();

			// check that adapter supports streaming before using it
			if (is_resource($body) && !($this->adapter instanceof HttpClientAdapterStream))
			{
				throw new bException('Adapter does not support streaming');
			}

			// Open the connection, send the request and read the response
			$this->adapter->connect($uri->getHost(), $uri->getPort(), ($uri->getScheme() == 'https' ? true : false));

			if($this->config['output_stream'])
			{
				if($this->adapter instanceof HttpClientAdapterStream)
				{
					$stream = $this->_openTempStream();
					$this->adapter->setOutputStream($stream);
				}
				else
				{
					throw new bException('Adapter does not support streaming');
				}
			}

			$this->lastRequest = $this->adapter->write($this->method, $uri, $this->config['httpversion'], $headers, $body);

			$response = $this->adapter->read();

			if (!$response)
			{
				throw new bException('Unable to read response, or response is empty');
			}

			if($this->config['output_stream'])
			{
				rewind($stream);

				// cleanup the adapter
				$this->adapter->setOutputStream(null);
				$response = HttpResponseStream::fromStream($response, $stream);
				$response->setStreamName($this->_streamName);

				if (!is_string($this->config['output_stream']))
				{
					// we used temp name, will need to clean up
					$response->setCleanup(true);
				}
			}
			else
			{
				$response = HttpResponse::fromString($response);
			}

			if ($this->config['storeresponse'])
			{
				$this->lastResponse = $response;
			}

			// Load cookies into cookie jar
			if (isset($this->cookieJar))
			{
				$this->cookieJar->addCookiesFromResponse($response, $uri, $this->config['encodecookies']);
			}

			// If we got redirected, look for the Location header
			if ($response->isRedirect() && ($location = $response->getHeader('location')))
			{
				// Avoid problems with buggy servers that add whitespace at the
				// end of some headers (See ZF-11283)
				$location = trim($location);

				// Check whether we send the exact same request again, or drop the parameters and send a GET request
				if ($response->getStatus() == 303 || ((! $this->config['strictredirects']) && ($response->getStatus() == 302 || $response->getStatus() == 301)))
				{
					$this->resetParameters();
					$this->setMethod(self::GET);
				}

				// If we got a well formed absolute URI
				if (($scheme = substr($location, 0, 6)) && ($scheme == 'http:/' || $scheme == 'https:'))
				{
					$this->setHeaders('host', null);
					$this->setUri($location);
				}
				else
				{
					// Split into path and query and set the query
					if (strpos($location, '?') !== false)
					{
						list($location, $query) = explode('?', $location, 2);
					}
					else
					{
						$query = '';
					}

					$this->uri->setQuery($query);

					// else, if we got just an absolute path, set it
					if (strpos($location, '/') === 0)
					{
						$this->uri->setPath($location);
					}
					// else, assume we have a relative path
					else
					{
						// Get the current path directory, removing any trailing slashes
						$path = $this->uri->getPath();
						$path = rtrim(substr($path, 0, strrpos($path, '/')), "/");
						$this->uri->setPath($path . '/' . $location);
					}
				}

				++$this->redirectCounter;
			}
			else
			{
				// If we didn't get any location, stop redirecting
				break;
			}
		}
		while ($this->redirectCounter < $this->config['maxredirects']);

		return $response;
	}

	/**
	 * Prepare the request headers
	 * @return array
	 */
	protected function _prepareHeaders()
	{
		$headers = array();

		// Set the host header
		if (!isset($this->headers['host']))
		{
			$host = $this->uri->getHost();

			// If the port is not default, add it
			if (!(($this->uri->getScheme() == 'http' && $this->uri->getPort() == 80) || ($this->uri->getScheme() == 'https' && $this->uri->getPort() == 443)))
			{
				$host .= ':'.$this->uri->getPort();
			}

			$headers[] = "Host: {$host}";
		}

		// Set the connection header
		if (!isset($this->headers['connection']))
		{
			if (!$this->config['keepalive'])
			{
				$headers[] = "Connection: close";
			}
		}

		// Set the Accept-encoding header if not set - depending on whether zlib is available or not.
		if (! isset($this->headers['accept-encoding']))
		{
			if (function_exists('gzinflate'))
			{
				$headers[] = 'Accept-encoding: gzip, deflate';
			}
			else
			{
				$headers[] = 'Accept-encoding: identity';
			}
		}

		// Set the Content-Type header
		if (($this->method == self::POST || $this->method == self::PUT) &&
		   (! isset($this->headers[strtolower(self::CONTENT_TYPE)]) && isset($this->encType)))
		{
			$headers[] = self::CONTENT_TYPE . ': ' . $this->encType;
		}

		// Set the user agent header
		if (!isset($this->headers['user-agent']) && isset($this->config['useragent']))
		{
			$headers[] = "User-Agent: {$this->config['useragent']}";
		}

		// Set HTTP authentication if needed
		if (is_array($this->auth))
		{
			$auth = self::encodeAuthHeader($this->auth['user'], $this->auth['password'], $this->auth['type']);
			$headers[] = "Authorization: {$auth}";
		}

		// Load cookies from cookie jar
		if (isset($this->cookieJar))
		{
			$cookieStr = $this->cookieJar->getMatchingCookies($this->uri, true, CookieJar::COOKIE_STRING_CONCAT);

			if ($cookieStr)
			{
				$headers[] = "Cookie: {$cookieStr}";
			}
		}

		// Add all other user defined headers
		foreach ($this->headers as $header)
		{
			list($name, $value) = $header;

			if (is_array($value))
			{
				$value = implode(', ', $value);
			}

			$headers[] = "$name: $value";
		}

		return $headers;
	}

	/**
	 * Prepare the request body (for POST and PUT requests)
	 * @return string
	 * @throws bException
	 */
	protected function _prepareBody()
	{
		// According to RFC2616, a TRACE request should not have a body.
		if ($this->method == self::TRACE)
		{
			return '';
		}

		if (isset($this->rawPostData) && is_resource($this->rawPostData))
		{
			return $this->rawPostData;
		}

		// If mbstring overloads substr and strlen functions, we have to override it's internal encoding
		if (function_exists('mb_internal_encoding') && ((int)ini_get('mbstring.func_overload')) & 2)
		{
			$mbIntEnc = mb_internal_encoding();
			mb_internal_encoding('ASCII');
		}

		// If we have rawPostData set, just use it as the body.
		if (isset($this->rawPostData))
		{
			$this->setHeaders(self::CONTENT_LENGTH, strlen($this->rawPostData));

			if (isset($mbIntEnc))
			{
				mb_internal_encoding($mbIntEnc);
			}

			return $this->rawPostData;
		}

		$body = '';

		// If we have files to upload, force encType to multipart/form-data
		if (count ($this->files) > 0) $this->setEncType(self::ENC_FORMDATA);

		// If we have POST parameters or files, encode and add them to the body
		if (count($this->paramsPost) > 0 || count($this->files) > 0)
		{
			switch($this->encType)
			{
				case self::ENC_FORMDATA:
					// Encode body as multipart/form-data
					$boundary = '---BLOCKSHTTPCLIENT-' . md5(microtime());
					$this->setHeaders(self::CONTENT_TYPE, self::ENC_FORMDATA . "; boundary={$boundary}");

					// Get POST parameters and encode them
					$params = self::_flattenParametersArray($this->paramsPost);
					foreach ($params as $pp)
					{
						$body .= self::encodeFormData($boundary, $pp[0], $pp[1]);
					}

					// Encode files
					foreach ($this->files as $file)
					{
						$fhead = array(self::CONTENT_TYPE => $file['ctype']);
						$body .= self::encodeFormData($boundary, $file['formname'], $file['data'], $file['filename'], $fhead);
					}

					$body .= "--{$boundary}--\r\n";
					break;

				case self::ENC_URLENCODED:
					// Encode body as application/x-www-form-urlencoded
					$this->setHeaders(self::CONTENT_TYPE, self::ENC_URLENCODED);
					$body = http_build_query($this->paramsPost, '', '&');
					break;

				default:
					if (isset($mbIntEnc))
					{
						mb_internal_encoding($mbIntEnc);
					}
						
					throw new bException("Cannot handle content type '{$this->encType}' automatically."." Please use HttpClient::setRawData to send this kind of content.");
					break;
			}
		}

		// Set the content-length if we have a body or if request is POST/PUT
		if ($body || $this->method == self::POST || $this->method == self::PUT)
		{
			$this->setHeaders(self::CONTENT_LENGTH, strlen($body));
		}

		if (isset($mbIntEnc))
		{
			mb_internal_encoding($mbIntEnc);
		}

		return $body;
	}

	/**
	 * Helper method that gets a possibly multi-level parameters array (get or post) and flattens it.
	 * The method returns an array of (key, value) pairs (because keys are not
	 * necessarily unique. If one of the parameters in as array, it will also
	 * add a [] suffix to the key.
	 * This method is deprecated since Zend Framework 1.9 in favour of self::_flattenParametersArray() and will be dropped in 2.0
	 * @deprecated since 1.9
	 * @param array $pArray The parameters array
	 * @param bool $urlEncode Whether to urlEncode the name and value
	 * @return array
	 */
	protected function _getParametersRecursive($pArray, $urlEncode = false)
	{
		if (!is_array($pArray))
			return $pArray;

		$parameters = array();

		foreach ($pArray as $name => $value)
		{
			if ($urlEncode)
				$name = urlencode($name);

			// If $value is an array, iterate over it
			if (is_array($value))
			{
				$name .= ($urlEncode ? '%5B%5D' : '[]');

				foreach ($value as $subval)
				{
					if ($urlEncode)
					{
						$subval = urlencode($subval);
					}

					$parameters[] = array($name, $subval);
				}
			}
			else
			{
				if ($urlEncode)
				{
					$value = urlencode($value);
				}

				$parameters[] = array($name, $value);
			}
		}

		return $parameters;
	}

	/**
	 * Attempt to detect the MIME type of a file using available extensions
	 * This method will try to detect the MIME type of a file. If the fileInfo
	 * extension is available, it will be used. If not, the mime_magic
	 * extension which is deprecated but is still available in many PHP setups
	 * will be tried.
	 * If neither extension is available, the default application/octet-stream
	 * MIME type will be returned
	 * @param string $file File path
	 * @return string MIME type
	 */
	protected function _detectFileMimeType($file)
	{
		$type = null;

		// First try with fileInfo functions
		if (function_exists('finfo_open'))
		{
			if (self::$_fileInfoDb === null)
			{
				self::$_fileInfoDb = @finfo_open(FILEINFO_MIME);
			}

			if (self::$_fileInfoDb)
			{
				$type = finfo_file(self::$_fileInfoDb, $file);
			}
		}
		else
		{
			if (function_exists('mime_content_type'))
			{
				$type = mime_content_type($file);
			}
		}

		// fallback to the default application/octet-stream
		if (!$type)
		{
			$type = 'application/octet-stream';
		}

		return $type;
	}

	/**
	 * Encode data to a multipart/form-data part suitable for a POST request.
	 * @param string $boundary
	 * @param string $name
	 * @param mixed $value
	 * @param string $filename
	 * @param array $headers Associative array of optional headers @example ("Content-transfer-encoding" => "binary")
	 * @return string
	 */
	public static function encodeFormData($boundary, $name, $value, $filename = null, $headers = array())
	{
		$ret = "--{$boundary}\r\n".'Content-Disposition: form-data; name="'.$name.'"';

		if ($filename)
		{
			$ret .= '; filename="'.$filename.'"';
		}

		$ret .= "\r\n";

		foreach ($headers as $hname => $hvalue)
		{
			$ret .= "{$hname}: {$hvalue}\r\n";
		}

		$ret .= "\r\n";

		$ret .= "{$value}\r\n";

		return $ret;
	}

	/**
	 * Create a HTTP authentication "Authorization:" header according to the
	 * specified user, password and authentication method.
	 * @see http://www.faqs.org/rfcs/rfc2617.html
	 * @param string $user
	 * @param string $password
	 * @param string $type
	 * @return string
	 * @throws bException
	 */
	public static function encodeAuthHeader($user, $password, $type = self::AUTH_BASIC)
	{
		$authHeader = null;

		switch ($type)
		{
			case self::AUTH_BASIC:
				// In basic authentication, the user name cannot contain ":"
				if (strpos($user, ':') !== false)
				{
					throw new bException("The user name cannot contain ':' in 'Basic' HTTP authentication");
				}

				$authHeader = 'Basic '.base64_encode($user.':'.$password);
				break;

			//case self::AUTH_DIGEST:
				/**
				 * @todo Implement digest authentication
				 */
			//    break;

			default:
				throw new bException("Not a supported HTTP authentication type: '$type'");
		}

		return $authHeader;
	}
	/**
	 * Convert an array of parameters into a flat array of (key, value) pairs
	 * Will flatten a potentially multi-dimentional array of parameters (such
	 * as POST parameters) into a flat array of (key, value) paris. In case
	 * of multi-dimentional arrays, square brackets ([]) will be added to the
	 * key to indicate an array.
	 * @since  1.9
	 * @param  array  $pArray
	 * @param  string $prefix
	 * @return array
	 */
	static protected function _flattenParametersArray($pArray, $prefix = null)
	{
		if (!is_array($pArray))
		{
			return $pArray;
		}

		$parameters = array();

		foreach($pArray as $name => $value)
		{

			// Calculate array key
			if ($prefix)
			{
				if (is_int($name))
				{
					$key = $prefix . '[]';
				}
				else
				{
					$key = $prefix . "[$name]";
				}
			}
			else
			{
				$key = $name;
			}

			if (is_array($value))
			{
				$parameters = array_merge($parameters, self::_flattenParametersArray($value, $key));
			}
			else
			{
				$parameters[] = array($key, $value);
			}
		}

		return $parameters;
	}
}
