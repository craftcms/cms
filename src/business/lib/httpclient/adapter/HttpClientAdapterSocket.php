<?php
/**
 * Modified version of Socket Adapter of Zend
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
 * A sockets based (stream_socket_client) adapter class for HttpClient. Can be used
 * on almost every PHP environment, and does not require any special extensions.
 *
 */

class HttpClientAdapterSocket implements HttpClientAdapterInterface, HttpClientAdapterStream
{
	/**
	 * The socket for server connection
	 * @var resource|null
	 */
	protected $socket = null;

	/**
	 * What host/port are we connected to?
	 * @var array
	 */
	protected $connectedTo = array(null, null);

	/**
	 * Stream for storing output
	 * @var resource
	 */
	protected $outStream = null;

	/**
	 * Parameters array
	 * @var array
	 */
	protected $config = array(
		'persistent'    => false,
		'ssltransport'  => 'ssl',
		'sslcert'       => null,
		'sslpassphrase' => null,
		'sslusecontext' => false
	);

	/**
	 * Request method - will be set by write() and might be used by read()
	 * @var string
	 */
	protected $method = null;

	/**
	 * Stream context
	 * @var resource
	 */
	protected $_context = null;

	/**
	 * Adapter constructor, currently empty. Config is set using setConfig()
	 */
	public function __construct()
	{
	}

	/**
	 * Set the configuration array for the adapter
	 * @param array $config
	 */
	public function setConfig($config = array())
	{
		if (!is_array($config))
		{
			throw new bException('$config expects an array, '.gettype($config).' received.');
		}

		foreach ($config as $k => $v)
		{
			$this->config[strtolower($k)] = $v;
		}
	}

	/**
	  * Retrieve the array of all configuration options
	  *
	  * @return array
	  */
	 public function getConfig()
	 {
		 return $this->config;
	 }

	 /**
	 * Set the stream context for the TCP connection to the server
	 * Can accept either a pre-existing stream context resource, or an array
	 * of stream options, similar to the options array passed to the
	 * stream_context_create() PHP function. In such case a new stream context
	 * will be created using the passed options.
	 * @param  mixed $context Stream context or array of context options
	 * @return HttpClientAdapterSocket
	 */
	public function setStreamContext($context)
	{
		if (is_resource($context) && get_resource_type($context) == 'stream-context')
		{
			$this->_context = $context;

		}
		else
		{
			if (is_array($context))
			{
				$this->_context = stream_context_create($context);
			}
			else
			{
				// Invalid parameter
				throw new bException("Expecting either a stream context resource or array, got ".gettype($context));
			}
		}

		return $this;
	}

	/**
	 * Get the stream context for the TCP connection to the server.
	 * If no stream context is set, will create a default one.
	 * @return resource
	 */
	public function getStreamContext()
	{
		if (! $this->_context)
		{
			$this->_context = stream_context_create();
		}

		return $this->_context;
	}

	/**
	 * Connect to the remote server
	 * @param string  $host
	 * @param int     $port
	 * @param boolean $secure
	 */
	public function connect($host, $port = 80, $secure = false)
	{
		// If the URI should be accessed via SSL, prepend the Hostname with ssl://
		$host = ($secure ? $this->config['ssltransport'] : 'tcp').'://'.$host;

		// If we are connected to the wrong host, disconnect first
		if (($this->connectedTo[0] != $host || $this->connectedTo[1] != $port))
		{
			if (is_resource($this->socket))
			{
				$this->close();
			}
		}

		// Now, if we are not connected, connect
		if (!is_resource($this->socket) || !$this->config['keepalive'])
		{
			$context = $this->getStreamContext();

			if ($secure || $this->config['sslusecontext'])
			{
				if ($this->config['sslcert'] !== null)
				{
					if (!stream_context_set_option($context, 'ssl', 'local_cert', $this->config['sslcert']))
					{
					   throw new bException('Unable to set sslcert option');
					}
				}

				if ($this->config['sslpassphrase'] !== null)
				{
					if (!stream_context_set_option($context, 'ssl', 'passphrase', $this->config['sslpassphrase']))
					{
						throw new bException('Unable to set sslpassphrase option');
					}
				}
			}

			$flags = STREAM_CLIENT_CONNECT;
			if ($this->config['persistent'])
			{
				$flags |= STREAM_CLIENT_PERSISTENT;
			}

			$this->socket = @stream_socket_client($host . ':' . $port,
												  $errno,
												  $errstr,
												  (int) $this->config['timeout'],
												  $flags,
												  $context);

			if (!$this->socket)
			{
				$this->close();
				throw new bException('Unable to Connect to '.$host.':'.$port.'. Error #'.$errno.': '.$errstr);
			}

			// Set the stream timeout
			if (!stream_set_timeout($this->socket, (int) $this->config['timeout']))
			{
				throw new bException('Unable to set the connection timeout');
			}

			// Update connected_to
			$this->connectedTo = array($host, $port);
		}
	}

	/**
	 * Send request to the remote server
	 * @param string        $method
	 * @param UriHttp 		$uri
	 * @param string        $http_ver
	 * @param array         $headers
	 * @param string        $body
	 * @return string Request as string
	 */
	public function write($method, $uri, $http_ver = '1.1', $headers = array(), $body = '')
	{
		// Make sure we're properly connected
		if (!$this->socket)
		{
			throw new bException('Trying to write but we are not connected');
		}

		$host = $uri->getHost();
		$host = (strtolower($uri->getScheme()) == 'https' ? $this->config['ssltransport'] : 'tcp').'://'.$host;

		if ($this->connectedTo[0] != $host || $this->connectedTo[1] != $uri->getPort())
		{
			throw new bException('Trying to write but we are connected to the wrong host');
		}

		// Save request method for later
		$this->method = $method;

		// Build request headers
		$path = $uri->getPath();

		if ($uri->getQuery())
		{
			$path .= '?'.$uri->getQuery();
		}

		$request = "{$method} {$path} HTTP/{$http_ver}\r\n";

		foreach ($headers as $k => $v)
		{
			if (is_string($k))
			{
				$v = ucfirst($k) . ": $v";
			}

			$request .= "$v\r\n";
		}

		if (is_resource($body))
		{
			$request .= "\r\n";
		}
		else
		{
			// Add the request body
			$request .= "\r\n".$body;
		}

		// Send the request
		if (!@fwrite($this->socket, $request))
		{
			throw new bException('Error writing request to server');
		}

		if (is_resource($body))
		{
			if (stream_copy_to_stream($body, $this->socket) == 0)
			{
				throw new bException('Error writing request to server');
			}
		}

		return $request;
	}

	/**
	 * Read response from server
	 * @return string
	 */
	public function read()
	{
		// First, read headers only
		$response = '';
		$gotStatus = false;
		$stream = !empty($this->config['stream']);

		while (($line = @fgets($this->socket)) !== false)
		{
			$gotStatus = $gotStatus || (strpos($line, 'HTTP') !== false);
			if ($gotStatus)
			{
				$response .= $line;
				if (rtrim($line) === '')
				{
					break;
				}
			}
		}

		$this->_checkSocketReadTimeout();

		$statusCode = HttpResponse::extractCode($response);

		// Handle 100 and 101 responses internally by restarting the read again
		if ($statusCode == 100 || $statusCode == 101)
		{
			return $this->read();
		}

		// Check headers to see what kind of connection / transfer encoding we have
		$headers = HttpResponse::extractHeaders($response);

		/**
		 * Responses to HEAD requests and 204 or 304 responses are not expected to have a body - stop reading here
		 */
		if ($statusCode == 304 || $statusCode == 204 || $this->method == HttpClient::HEAD)
		{
			// Close the connection if requested to do so by the server
			if (isset($headers['connection']) && $headers['connection'] == 'close')
			{
				$this->close();
			}

			return $response;
		}

		// If we got a 'transfer-encoding: chunked' header
		if (isset($headers['transfer-encoding']))
		{
			if (strtolower($headers['transfer-encoding']) == 'chunked')
			{
				do
				{
					$line  = @fgets($this->socket);
					$this->_checkSocketReadTimeout();

					$chunk = $line;

					// Figure out the next chunk size
					$chunksize = trim($line);
					if (!ctype_xdigit($chunksize))
					{
						$this->close();
						throw new bException('Invalid chunk size "'.$chunksize.'" unable to read chunked body');
					}

					// Convert the hexadecimal value to plain integer
					$chunksize = hexdec($chunksize);

					// Read next chunk
					$read_to = ftell($this->socket) + $chunksize;

					do
					{
						$current_pos = ftell($this->socket);
						if ($current_pos >= $read_to)
						{
							break;
						}

						if ($this->outStream)
						{
							if (stream_copy_to_stream($this->socket, $this->outStream, $read_to - $current_pos) == 0)
							{
							  $this->_checkSocketReadTimeout();
							  break;
							}

						}
						else
						{
							$line = @fread($this->socket, $read_to - $current_pos);
							if ($line === false || strlen($line) === 0)
							{
								$this->_checkSocketReadTimeout();
								break;
							}

							$chunk .= $line;
						}
					} while (!feof($this->socket));

					$chunk .= @fgets($this->socket);
					$this->_checkSocketReadTimeout();

					if(!$this->outStream)
					{
						$response .= $chunk;
					}
				} while ($chunksize > 0);
			}
			else
			{
				$this->close();
				throw new bException('Cannot handle "'.$headers['transfer-encoding'].'" transfer encoding');
			}

			// We automatically decode chunked-messages when writing to a stream
			// this means we have to disallow the HttpResponse to do it again
			if ($this->outStream)
			{
				$response = str_ireplace("Transfer-Encoding: chunked\r\n", '', $response);
			}

		// else, if we got the content-length header, read this number of bytes
		}
		else
		{
			if (isset($headers['content-length']))
			{
				// If we got more than one Content-Length header (see ZF-9404) use the last value sent
				if (is_array($headers['content-length']))
				{
					$contentLength = $headers['content-length'][count($headers['content-length']) - 1];
				}
				else
				{
					$contentLength = $headers['content-length'];
				}

				$current_pos = ftell($this->socket);
				$chunk = '';

				for ($read_to = $current_pos + $contentLength; $read_to > $current_pos; $current_pos = ftell($this->socket))
				{
					if($this->outStream)
					{
						if (@stream_copy_to_stream($this->socket, $this->outStream, $read_to - $current_pos) == 0)
						{
							$this->_checkSocketReadTimeout();
							break;
						}
					}
					else
					{
						$chunk = @fread($this->socket, $read_to - $current_pos);

						if ($chunk === false || strlen($chunk) === 0)
						{
							$this->_checkSocketReadTimeout();
							break;
						}

						$response .= $chunk;
					}

					// Break if the connection ended prematurely
					if (feof($this->socket))
					{
						break;
					}
				}

				// Fallback: just read the response until EOF
			}
			else
			{
				do
				{
					if ($this->outStream)
					{
						if (@stream_copy_to_stream($this->socket, $this->outStream) == 0)
						{
							$this->_checkSocketReadTimeout();
							break;
						}
					}
					else
					{
						$buff = @fread($this->socket, 8192);

						if ($buff === false || strlen($buff) === 0)
						{
							$this->_checkSocketReadTimeout();
							break;
						}
						else
						{
							$response .= $buff;
						}
					}

				} while (feof($this->socket) === false);

				$this->close();
			}
		}

		// Close the connection if requested to do so by the server
		if (isset($headers['connection']) && $headers['connection'] == 'close')
		{
			$this->close();
		}

		return $response;
	}

	/**
	 * Close the connection to the server
	 */
	public function close()
	{
		if (is_resource($this->socket))
		{
			@fclose($this->socket);
		}

		$this->socket = null;
		$this->connectedTo = array(null, null);
	}

	/**
	 * Check if the socket has timed out - if so close connection and throw
	 * an exception
	 * @throws bException with READ_TIMEOUT code
	 */
	protected function _checkSocketReadTimeout()
	{
		if ($this->socket)
		{
			$info = stream_get_meta_data($this->socket);
			$timedout = $info['timed_out'];

			if ($timedout)
			{
				$this->close();
				throw new bException("Read timed out after {$this->config['timeout']} seconds");
			}
		}
	}

	/**
	 * Set output stream for the response
	 * @param resource $stream
	 * @return HttpClientAdapterSocket
	 */
	public function setOutputStream($stream)
	{
		$this->outStream = $stream;
		return $this;
	}

	/**
	 * Destructor: make sure the socket is disconnected
	 * If we are in persistent TCP mode, will not close the connection
	 */
	public function __destruct()
	{
		if (!$this->config['persistent'])
		{
			if ($this->socket)
			{
				$this->close();
			}
		}
	}
}
