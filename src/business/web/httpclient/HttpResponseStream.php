<?php

/**
 * Modified version of Zend_Http_Response of Zend
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
 * HttpResponse represents an HTTP 1.0 / 1.1 response message. It
 * includes easy access to all the response's different elements, as well as some
 * convenience methods for parsing and validating HTTP responses.
 */

class HttpResponseStream extends HttpResponse
{
	/**
	 * Response as stream
	 *
	 * @var resource
	 */
	protected $stream;

	/**
	 * The name of the file containing the stream
	 *
	 * Will be empty if stream is not file-based.
	 *
	 * @var string
	 */
	protected $streamName;

	/**
	 * Should we clean up the stream file when this response is closed?
	 *
	 * @var boolean
	 */
	protected $_cleanup;

	/**
	 * Get the response as stream
	 *
	 * @return resource
	 */
	public function getStream()
	{
		return $this->stream;
	}

	/**
	 * Set the response stream
	 *
	 * @param resource $stream
	 * @return Zend_Http_Response_Stream
	 */
	public function setStream($stream)
	{
		$this->stream = $stream;
		return $this;
	}

	/**
	 * Get the cleanup trigger
	 *
	 * @return boolean
	 */
	public function getCleanup()
	{
		return $this->_cleanup;
	}

	/**
	 * Set the cleanup trigger
	 *
	 * @param bool $cleanup Set cleanup trigger
	 */
	public function setCleanup($cleanup = true)
	{
		$this->_cleanup = $cleanup;
	}

	/**
	 * Get file name associated with the stream
	 *
	 * @return string
	 */
	public function getStreamName()
	{
		return $this->streamName;
	}

	/**
	 * Set file name associated with the stream
	 *
	 * @param string $streamName Name to set
	 * @return Zend_Http_Response_Stream
	 */
	public function setStreamName($streamName)
	{
		$this->streamName = $streamName;
		return $this;
	}


	/**
	 * HTTP response constructor
	 *
	 * In most cases, you would use HttpResponse::fromString to parse an HTTP response string and create a new HttpResponse object.
	 *
	 * NOTE: The constructor no longer accepts nulls or empty values for the code and
	 * headers and will throw an exception if the passed values do not form a valid HTTP
	 * responses.
	 *
	 * If no message is passed, the message will be guessed according to the response code.
	 *
	 * @param int $code Response code (200, 404, ...)
	 * @param array $headers Headers array
	 * @param string $body Response body
	 * @param string $version HTTP version
	 * @param string $message Response code as text
	 * @throws HttpException
	 */
	public function __construct($code, $headers, $body = null, $version = '1.1', $message = null)
	{
		if(is_resource($body))
		{
			$this->setStream($body);
			$body = '';
		}

		parent::__construct($code, $headers, $body, $version, $message);
	}

	/**
	 * Create a new HttpResponseStream object from a string
	 *
	 * @param string $responseStr
	 * @param resource $stream
	 * @return HttpResponseStream
	 */
	public static function fromStream($responseStr, $stream)
	{
		$code    = self::extractCode($responseStr);
		$headers = self::extractHeaders($responseStr);
		$version = self::extractVersion($responseStr);
		$message = self::extractMessage($responseStr);

		return new self($code, $headers, $stream, $version, $message);
	}

	/**
	 * Get the response body as string
	 *
	 * This method returns the body of the HTTP response (the content), as it
	 * should be in it's readable version - that is, after decoding it (if it
	 * was decoded), deflating it (if it was gzip compressed), etc.
	 *
	 * If you want to get the raw body (as transferred on wire) use
	 * $this->getRawBody() instead.
	 *
	 * @return string
	 */
	public function getBody()
	{
		if($this->stream != null)
		{
			$this->readStream();
		}

		return parent::getBody();
	}

	/**
	 * Get the raw response body (as transferred "on wire") as string
	 *
	 * If the body is encoded (with Transfer-Encoding, not content-encoding -
	 * IE "chunked" body), gzip compressed, etc. it will not be decoded.
	 *
	 * @return string
	 */
	public function getRawBody()
	{
		if($this->stream)
		{
			$this->readStream();
		}

		return $this->body;
	}

	/**
	 * Read stream content and return it as string
	 *
	 * Function reads the remainder of the body from the stream and closes the stream.
	 *
	 * @return string
	 */
	protected function readStream()
	{
		if (!is_resource($this->stream))
		{
			return '';
		}

		if (isset($headers['content-length']))
		{
			$this->body = stream_get_contents($this->stream, $headers['content-length']);
		}
		else
		{
			$this->body = stream_get_contents($this->stream);
		}

		fclose($this->stream);
		$this->stream = null;
	}

	public function __destruct()
	{
		if (is_resource($this->stream))
		{
			fclose($this->stream);
			$this->stream = null;
		}

		if($this->_cleanup)
		{
			@unlink($this->streamName);
		}
	}
}
