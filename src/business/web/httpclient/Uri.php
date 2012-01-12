<?php

/**
 * Modified version of Zend_Uri of Zend
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
 * Abstract class for all Uri handlers
 */
abstract class Uri
{
	/**
	 * Scheme of this URI (http, ftp, etc.)
	 * @var string
	 */
	protected $_scheme = '';

	/**
		* Global configuration array
	*
		* @var array
		*/
		static protected $_config = array(
			'allow_unwise' => false
		);

		/**
	 * Return a string representation of this URI.
	 * @see getUri()
	 * @return string
	 */
	public function __toString()
	{
		return $this->getUri();
	}

	/**
	 * Convenience function, checks that a $uri string is well-formed
	 * by validating it but not returning an object.  Returns TRUE if
	 * $uri is a well-formed URI, or FALSE otherwise.
	 * @param string $uri The URI to check
	 * @return boolean
	 */
	public static function check($uri)
	{
		try
		{
			$uri = self::factory($uri);
		}
		catch (Exception $e)
		{
			return false;
		}

		return $uri->valid();
	}

	/**
	 * Create a new Uri object for a URI.  If building a new URI, then $uri should contain
	 * only the scheme (http, ftp, etc).  Otherwise, supply $uri with the complete URI.
	 * @param  string $uri The URI form which a Uri instance is created
	 * @throws BlocksException When an empty string was supplied for the scheme
	 * @throws BlocksException When an illegal scheme is supplied
	 * @throws BlocksException When the scheme is not supported
	 * @throws BlocksException When $className doesn't exist or doesn't implement Uri
	 * @return Uri
	 * @link   http://www.faqs.org/rfcs/rfc2396.html
	 */
	public static function factory($uri = 'http')
	{
		// Separate the scheme from the scheme-specific parts
		$uri = explode(':', $uri, 2);
		$scheme = strtolower($uri[0]);
		$schemeSpecific = isset($uri[1]) === true ? $uri[1] : '';

		if (strlen($scheme) === 0)
		{
			throw new BlocksException('An empty string was supplied for the scheme');
		}

		// Security check: $scheme is used to load a class file, so only alphanumerics are allowed.
		if (ctype_alnum($scheme) === false)
		{
			throw new BlocksException('Illegal scheme supplied, only alphanumeric characters are permitted');
		}

		/**
		* Create a new Uri object for the $uri. If a subclass of Uri exists for the
		* scheme, return an instance of that class. Otherwise, a CException is thrown.
		*/
		switch ($scheme)
		{
			case 'http':
				// Break intentionally omitted
			case 'https':
				$className = 'UriHttp';
				break;
			case 'mailto':
				// TODO
			default:
				throw new BlocksException("Scheme \"$scheme\" is not supported");
				break;
		}

		$schemeHandler = new $className($scheme, $schemeSpecific);
			if (!$schemeHandler instanceof Uri)
		{
					throw new BlocksException("\"$className\" is not an instance of Uri");
		}

		return $schemeHandler;
	}

	/**
	 * Get the URI's scheme
	 * @return string|false Scheme or false if no scheme is set.
	 */
	public function getScheme()
	{
		if (empty($this->_scheme) === false)
		{
			return $this->_scheme;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Uri and its subclasses cannot be instantiated directly.
	 * Use Uri::factory() to return a new Uri object.
	 * @param string $scheme         The scheme of the URI
	 * @param string $schemeSpecific The scheme-specific part of the URI
	 */
	abstract protected function __construct($scheme, $schemeSpecific = '');

	/**
	 * Return a string representation of this URI.
	 * @return string
	 */
	abstract public function getUri();

	/**
	 * Returns TRUE if this URI is valid, or FALSE otherwise.
	 * @return boolean
	 */
	abstract public function valid();
}
