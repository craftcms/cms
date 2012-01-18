<?php
/**
 * Modified version of CUrl Client Adapter of Zend
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
 * An adapter class for HttpClient based on the curl extension.
 * Curl requires libcurl. See for full requirements the PHP manual: http://php.net/curl
 *
 */

class HttpClientAdapterCurl implements HttpClientAdapterInterface, HttpClientAdapterStream
{
	/**
	 * Parameters array
	 * @var array
	 */
	protected $_config = array();

	/**
	 * What host/port are we connected to?
	 * @var array
	 */
	protected $_connected_to = array(null, null);

	/**
	 * The curl session handle
	 * @var resource|null
	 */
	protected $_curl = null;

	/**
	 * List of cURL options that should never be overwritten
	 * @var array
	 */
	protected $_invalidOverwritableCurlOptions;

	/**
	 * Response gotten from server
	 * @var string
	 */
	protected $_response = null;

	/**
	 * Stream for storing output
	 * @var resource
	 */
	protected $out_stream;

	/**
	 * Adapter constructor
	 * Config is set using setConfig()
	 * @return \HttpClientAdapterCurl
	 * @throws HttpClientAdapterException
	 */
	public function __construct()
	{
		if (!extension_loaded('curl'))
		{
			throw new bException('cURL extension has to be loaded to use this HttpClient adapter.');
		}

		$this->_invalidOverwritableCurlOptions = array(
			CURLOPT_HTTPGET,
			CURLOPT_POST,
			CURLOPT_PUT,
			CURLOPT_CUSTOMREQUEST,
			CURLOPT_HEADER,
			CURLOPT_RETURNTRANSFER,
			CURLOPT_HTTPHEADER,
			CURLOPT_POSTFIELDS,
			CURLOPT_INFILE,
			CURLOPT_INFILESIZE,
			CURLOPT_PORT,
			CURLOPT_MAXREDIRS,
			CURLOPT_CONNECTTIMEOUT,
			CURL_HTTP_VERSION_1_1,
			CURL_HTTP_VERSION_1_0,
		);
	}

	/**
	 * Set the configuration array for the adapter
	 * @throws bException
	 * @param  array $config
	 * @return HttpClientAdapterCurl
	 */
	public function setConfig($config = array())
	{
		if (!is_array($config))
		{
			throw new bException('Array expected, got '.gettype($config));
		}

		if(isset($config['proxy_user']) && isset($config['proxy_pass']))
		{
			$this->setCurlOption(CURLOPT_PROXYUSERPWD, $config['proxy_user'].":".$config['proxy_pass']);
			unset($config['proxy_user'], $config['proxy_pass']);
		}

		foreach ($config as $k => $v)
		{
			$option = strtolower($k);

			switch($option)
			{
				case 'proxy_host':
					$this->setCurlOption(CURLOPT_PROXY, $v);
					break;
				case 'proxy_port':
					$this->setCurlOption(CURLOPT_PROXYPORT, $v);
					break;
				default:
					$this->_config[$option] = $v;
					break;
			}
		}

		return $this;
	}

	/**
	  * Retrieve the array of all configuration options
	  *
	  * @return array
	  */
	 public function getConfig()
	 {
		return $this->_config;
	 }

	/**
	 * Direct setter for cURL adapter related options.
	 * @param  string|int $option
	 * @param  mixed $value
	 * @return HttpAdapterCurl
	 */
	public function setCurlOption($option, $value)
	{
		if (!isset($this->_config['curloptions']))
		{
			$this->_config['curloptions'] = array();
		}

		$this->_config['curloptions'][$option] = $value;
		return $this;
	}

	/**
	 * Initialize curl
	 * @param  string  $host
	 * @param  int     $port
	 * @param  boolean $secure
	 * @return void
	 * @throws bException if unable to connect
	 */
	public function connect($host, $port = 80, $secure = false)
	{
		// If we're already connected, disconnect first
		if ($this->_curl)
		{
			$this->close();
		}

		// If we are connected to a different server or port, disconnect first
		if ($this->_curl && is_array($this->_connected_to) && ($this->_connected_to[0] != $host || $this->_connected_to[1] != $port))
		{
			$this->close();
		}

		// Do the actual connection
		$this->_curl = curl_init();

		if ($port != 80)
		{
			curl_setopt($this->_curl, CURLOPT_PORT, intval($port));
		}

		// Set timeout
		curl_setopt($this->_curl, CURLOPT_CONNECTTIMEOUT, $this->_config['timeout']);

		// Set Max redirects
		curl_setopt($this->_curl, CURLOPT_MAXREDIRS, $this->_config['maxredirects']);

		if (!$this->_curl)
		{
			$this->close();
			throw new bException('Unable to Connect to '.$host.':'.$port);
		}

		if ($secure !== false)
		{
			// Behave the same like HttpAdapterSocket on SSL options.
			if (isset($this->_config['sslcert']))
			{
				curl_setopt($this->_curl, CURLOPT_SSLCERT, $this->_config['sslcert']);
			}

			if (isset($this->_config['sslpassphrase']))
			{
				curl_setopt($this->_curl, CURLOPT_SSLCERTPASSWD, $this->_config['sslpassphrase']);
			}
		}

		// Update connected_to
		$this->_connected_to = array($host, $port);
	}

	/**
	 * Send request to the remote server
	 * @param  string        $method
	 * @param  UriHttp       $uri
	 * @param float          $httpVersion
	 * @param  array         $headers
	 * @param  string        $body
	 * @return string        $request
	 * @throws bException If connection fails, connected to wrong host, no PUT file defined, unsupported method, or unsupported cURL option
	 */
	public function write($method, $uri, $httpVersion = 1.1, $headers = array(), $body = '')
	{
		// Make sure we're properly connected
		if (!$this->_curl)
		{
			throw new bException("Trying to write but we are not connected");
		}

		if ($this->_connected_to[0] != $uri->getHost() || $this->_connected_to[1] != $uri->getPort())
		{
			throw new bException("Trying to write but we are connected to the wrong host");
		}

		// set URL
		curl_setopt($this->_curl, CURLOPT_URL, $uri->__toString());

		// ensure correct curl call
		$curlValue = true;

		switch ($method)
		{
			case HttpClient::GET:
				$curlMethod = CURLOPT_HTTPGET;
				break;

			case HttpClient::POST:
				$curlMethod = CURLOPT_POST;
				break;

			case HttpClient::PUT:
				// There are two different types of PUT request, either a Raw Data string has been set or CURLOPT_INFILE and CURLOPT_INFILESIZE are used.
				if(is_resource($body))
				{
					$this->_config['curloptions'][CURLOPT_INFILE] = $body;
				}

				if (isset($this->_config['curloptions'][CURLOPT_INFILE]))
				{
					// Now we will probably already have Content-Length set, so that we have to delete it from $headers at this point:
					foreach ($headers AS $k => $header)
					{
						if (preg_match('/Content-Length:\s*(\d+)/i', $header, $m))
						{
							if(is_resource($body))
							{
								$this->_config['curloptions'][CURLOPT_INFILESIZE] = (int)$m[1];
							}

							unset($headers[$k]);
						}
					}

					if (!isset($this->_config['curloptions'][CURLOPT_INFILESIZE]))
					{
						throw new bException("Cannot set a file-handle for cURL option CURLOPT_INFILE without also setting its size in CURLOPT_INFILESIZE.");
					}

					if(is_resource($body))
					{
						$body = '';
					}

					$curlMethod = CURLOPT_PUT;
				}
				else
				{
					$curlMethod = CURLOPT_CUSTOMREQUEST;
					$curlValue = "PUT";
				}

				break;

			case HttpClient::DELETE:
				$curlMethod = CURLOPT_CUSTOMREQUEST;
				$curlValue = "DELETE";
				break;

			case HttpClient::OPTIONS:
				$curlMethod = CURLOPT_CUSTOMREQUEST;
				$curlValue = "OPTIONS";
				break;

			case HttpClient::TRACE:
				$curlMethod = CURLOPT_CUSTOMREQUEST;
				$curlValue = "TRACE";
				break;

			case HttpClient::HEAD:
				$curlMethod = CURLOPT_CUSTOMREQUEST;
				$curlValue = "HEAD";
				break;

			default:
				// For now, through an exception for unsupported request methods
				throw new bException("Method currently not supported");
		}

		if(is_resource($body) && $curlMethod != CURLOPT_PUT)
		{
			throw new bException("Streaming requests are allowed only with PUT");
		}

		// get http version to use
		$curlHttp = ($httpVersion == 1.1) ? CURL_HTTP_VERSION_1_1 : CURL_HTTP_VERSION_1_0;

		// mark as HTTP request and set HTTP method
		curl_setopt($this->_curl, $curlHttp, true);
		curl_setopt($this->_curl, $curlMethod, $curlValue);

		if($this->out_stream)
		{
			// headers will be read into the response
			curl_setopt($this->_curl, CURLOPT_HEADER, false);
			curl_setopt($this->_curl, CURLOPT_HEADERFUNCTION, array($this, "readHeader"));
			// and data will be written into the file
			curl_setopt($this->_curl, CURLOPT_FILE, $this->out_stream);
		}
		else
		{
			// ensure headers are also returned
			curl_setopt($this->_curl, CURLOPT_HEADER, true);

			// ensure actual response is returned
			curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);
		}

		// set additional headers
		$headers['Accept'] = '';
		curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);

		/**
		 * Make sure POSTFIELDS is set after $curlMethod is set:
		 * @link http://de2.php.net/manual/en/function.curl-setopt.php#81161
		 */
		if ($method == HttpClient::POST)
		{
			curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $body);
		}
		else
		{
			if ($curlMethod == CURLOPT_PUT)
			{
				// this covers a PUT by file-handle:
				// Make the setting of this options explicit (rather than setting it through the loop following a bit lower) to group common functionality together.
				curl_setopt($this->_curl, CURLOPT_INFILE, $this->_config['curloptions'][CURLOPT_INFILE]);
				curl_setopt($this->_curl, CURLOPT_INFILESIZE, $this->_config['curloptions'][CURLOPT_INFILESIZE]);
				unset($this->_config['curloptions'][CURLOPT_INFILE]);
				unset($this->_config['curloptions'][CURLOPT_INFILESIZE]);
			}
			else
			{
				if ($method == HttpClient::PUT)
				{
					// This is a PUT by a setRawData string, not by file-handle
					curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $body);
				}
			}
		}

		// set additional curl options
		if (isset($this->_config['curloptions']))
		{
			foreach ((array)$this->_config['curloptions'] as $k => $v)
			{
				if (!in_array($k, $this->_invalidOverwritableCurlOptions))
				{
					if (curl_setopt($this->_curl, $k, $v) == false)
					{
						throw new bException(sprintf("Unknown or erroreous cURL option '%s' set", $k));
					}
				}
			}
		}

		// send the request
		$response = curl_exec($this->_curl);

		// if we used streaming, headers are already there
		if(!is_resource($this->out_stream))
		{
			$this->_response = $response;
		}

		$request  = curl_getinfo($this->_curl, CURLINFO_HEADER_OUT);
		$request .= $body;

		if (empty($this->_response))
		{
			throw new bException("Error in cURL request: ".curl_error($this->_curl));
		}

		// cURL automatically decodes chunked-messages, this means we have to disallow the HttpResponse to do it again
		if (stripos($this->_response, "Transfer-Encoding: chunked\r\n"))
		{
			$this->_response = str_ireplace("Transfer-Encoding: chunked\r\n", '', $this->_response);
		}

		// Eliminate multiple HTTP responses.
		do
		{
			$parts = preg_split('|(?:\r?\n){2}|m', $this->_response, 2);
			$again = false;

			if (isset($parts[1]) && preg_match("|^HTTP/1\.[01](.*?)\r\n|mi", $parts[1]))
			{
				$this->_response = $parts[1];
				$again = true;
			}
		} while ($again);

		// cURL automatically handles Proxy rewrites, remove the "HTTP/1.0 200 Connection established" string:
		if (stripos($this->_response, "HTTP/1.0 200 Connection established\r\n\r\n") !== false)
		{
			$this->_response = str_ireplace("HTTP/1.0 200 Connection established\r\n\r\n", '', $this->_response);
		}

		return $request;
	}

	/**
	 * Return read response from server
	 * @return string
	 */
	public function read()
	{
		return $this->_response;
	}

	/**
	 * Close the connection to the server
	 */
	public function close()
	{
		if(is_resource($this->_curl))
		{
			curl_close($this->_curl);
		}

		$this->_curl = null;
		$this->_connected_to = array(null, null);
	}

	/**
	 * Get cUrl Handle
	 * @return resource
	 */
	public function getHandle()
	{
		return $this->_curl;
	}

	/**
	 * Set output stream for the response
	 * @param resource $stream
	 * @return HttpClientAdapterSocket
	 */
	public function setOutputStream($stream)
	{
		$this->out_stream = $stream;
		return $this;
	}

	/**
	 * Header reader function for CURL
	 * @param resource $curl
	 * @param string $header
	 * @return int
	 */
	public function readHeader($curl, $header)
	{
		$this->_response .= $header;
		return strlen($header);
	}
}
