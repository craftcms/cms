<?php
/**
 * Modified version of Zend_Http_Cookie of Zend
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
 * HttpCookie is a class describing an HTTP cookie and all it's parameters.
 *
 * HttpCookie is a class describing an HTTP cookie and all it's parameters. The
 * class also enables validating whether the cookie should be sent to the server in
 * a specified scenario according to the request URI, the expiry time and whether
 * session cookies should be used or not. Generally speaking cookies should be
 * contained in a CookieJar object, or instantiated manually and added to an HTTP
 * request.
 *
 * See http://wp.netscape.com/newsref/std/cookie_spec.html for some specs.
 */

class HttpCookie
{
	/**
	 * HttpCookie name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * HttpCookie value
	 *
	 * @var string
	 */
	protected $value;

	/**
	 * HttpCookie expiry date
	 *
	 * @var int
	 */
	protected $expires;

	/**
	 * HttpCookie domain
	 *
	 * @var string
	 */
	protected $domain;

	/**
	 * HttpCookie path
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Whether the cookie is secure or not
	 *
	 * @var boolean
	 */
	protected $secure;

	/**
	 * Whether the cookie value has been encoded/decoded
	 *
	 * @var boolean
	 */
	protected $encodeValue;

	/**
	 * HttpCookie object constructor
	 *
	 * @todo Add validation of each one of the parameters (legal domain, etc.)
	 *
	 * @param string $name
	 * @param string $value
	 * @param string $domain
	 * @param int $expires
	 * @param string $path
	 * @param bool $secure
	 */
	public function __construct($name, $value, $domain, $expires = null, $path = null, $secure = false)
	{
		if (preg_match("/[=,; \t\r\n\013\014]/", $name))
		{
			throw new BlocksException("Cookie name cannot contain these characters: =,; \\t\\r\\n\\013\\014 ({$name})");
		}

		if (! $this->name = (string)$name)
		{
			throw new BlocksException('Cookies must have a name');
		}

		if (! $this->domain = (string)$domain)
		{
			throw new BlocksException('Cookies must have a domain');
		}

		$this->value = (string)$value;
		$this->expires = ($expires === null ? null : (int)$expires);
		$this->path = ($path ? $path : '/');
		$this->secure = $secure;
	}

	/**
	 * Get HttpCookie name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get cookie value
	 *
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Get cookie domain
	 *
	 * @return string
	 */
	public function getDomain()
	{
		return $this->domain;
	}

	/**
	 * Get the cookie path
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Get the expiry time of the cookie, or null if no expiry time is set
	 *
	 * @return int|null
	 */
	public function getExpiryTime()
	{
		return $this->expires;
	}

	/**
	 * Check whether the cookie should only be sent over secure connections
	 *
	 * @return boolean
	 */
	public function isSecure()
	{
		return $this->secure;
	}

	/**
	 * Check whether the cookie has expired
	 *
	 * Always returns false if the cookie is a session cookie (has no expiry time)
	 *
	 * @param int $now Timestamp to consider as "now"
	 * @return boolean
	 */
	public function isExpired($now = null)
	{
		if ($now === null)
			$now = time();

		if (is_int($this->expires) && $this->expires < $now)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Check whether the cookie is a session cookie (has no expiry time set)
	 *
	 * @return boolean
	 */
	public function isSessionCookie()
	{
		return ($this->expires === null);
	}

	/**
	 * Checks whether the cookie should be sent or not in a specific scenario
	 *
	 * @param string|UriHttp $uri URI to check against (secure, domain, path)
	 * @param boolean $matchSessionCookies Whether to send session cookies
	 * @param int $now Override the current time when checking for expiry time
	 * @return boolean
	 */
	public function match($uri, $matchSessionCookies = true, $now = null)
	{
		if (is_string($uri))
		{
			$uri = UriHttp::factory($uri);
		}

		// Make sure we have a valid UriHttp object
		if (!($uri->valid() && ($uri->getScheme() == 'http' || $uri->getScheme() =='https')))
		{
			throw new CException('Passed URI is not a valid HTTP or HTTPS URI');
		}

		// Check that the cookie is secure (if required) and not expired
		if ($this->secure && $uri->getScheme() != 'https')
			return false;

		if ($this->isExpired($now))
			return false;

		if ($this->isSessionCookie() && ! $matchSessionCookies)
			return false;

		// Check if the domain matches
		if (!self::matchCookieDomain($this->getDomain(), $uri->getHost()))
		{
			return false;
		}

		// Check that path matches using prefix match
		if (!self::matchCookiePath($this->getPath(), $uri->getPath()))
		{
			return false;
		}

		// If we didn't die until now, return true.
		return true;
	}

	/**
	 * Get the cookie as a string, suitable for sending as a "HttpCookie" header in an HTTP request
	 *
	 * @return string
	 */
	public function __toString()
	{
		if ($this->encodeValue)
		{
			return $this->name.'='.urlencode($this->value).';';
		}

		return $this->name.'='.$this->value.';';
	}

	/**
	 * Generate a new HttpCookie object from a cookie string (for example the value of the Set-HttpCookie HTTP header)
	 *
	 * @param string $cookieStr
	 * @param UriHttp|string $refUri Reference URI for default values (domain, path)
	 * @param boolean $encodeValue Whether or not the cookie's value should be passed through urlencode/urldecode
	 * @return HttpCookie A new HttpCookie object or false on failure.
	 */
	public static function fromString($cookieStr, $refUri = null, $encodeValue = true)
	{
		// Set default values
		if (is_string($refUri))
		{
			$refUri = UriHttp::factory($refUri);
		}

		$name    = '';
		$value   = '';
		$domain  = '';
		$path    = '';
		$expires = null;
		$secure  = false;
		$parts   = explode(';', $cookieStr);

		// If first part does not include '=', fail
		if (strpos($parts[0], '=') === false)
			return false;

		// Get the name and value of the cookie
		list($name, $value) = explode('=', trim(array_shift($parts)), 2);
		$name = trim($name);
		if ($encodeValue) 
		{
			$value = urldecode(trim($value));
		}

		// Set default domain and path
		if ($refUri instanceof UriHttp)
		{
			$domain = $refUri->getHost();
			$path = $refUri->getPath();
			$path = substr($path, 0, strrpos($path, '/'));
		}

		// Set other cookie parameters
		foreach ($parts as $part)
		{
			$part = trim($part);
			if (strtolower($part) == 'secure')
			{
				$secure = true;
				continue;
			}

			$keyValue = explode('=', $part, 2);

			if (count($keyValue) == 2)
			{
				list($k, $v) = $keyValue;

				switch (strtolower($k))
				{
					case 'expires':
						$expires = strtotime($v);
						break;
					case 'path':
						$path = $v;
						break;
					case 'domain':
						$domain = $v;
						break;
					default:
						break;
				}
			}
		}

		if ($name !== '')
		{
			$ret = new self($name, $value, $domain, $expires, $path, $secure);
			$ret->encodeValue = ($encodeValue) ? true : false;
			return $ret;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Check if a cookie's domain matches a host name.
	 *
	 * Used by HttpCookie and CookieJar for cookie matching
	 *
	 * @param  string $cookieDomain
	 * @param  string $host
	 *
	 * @return boolean
	 */
	public static function matchCookieDomain($cookieDomain, $host)
	{
		if (!$cookieDomain)
		{
			throw new CException("\$cookieDomain is expected to be a cookie domain");
		}

		if (!$host)
		{
			throw new CException("\$host is expected to be a host name");
		}

		$cookieDomain = strtolower($cookieDomain);
		$host = strtolower($host);

		if ($cookieDomain[0] == '.')
		{
			$cookieDomain = substr($cookieDomain, 1);
		}

		// Check for either exact match or suffix match
		return ($cookieDomain == $host || preg_match('/\.'.preg_quote($cookieDomain).'$/', $host));
	}

	/**
	 * Check if a cookie's path matches a URL path
	 *
	 * Used by HttpCookie and CookieJar for cookie matching
	 *
	 * @param  string $cookiePath
	 * @param  string $path
	 * @return boolean
	 */
	public static function matchCookiePath($cookiePath, $path)
	{
		if (!$cookiePath)
		{
			throw new CException("\$cookiePath is expected to be a cookie path");
		}

		if (!$path)
		{
			throw new CException("\$path is expected to be a host name");
		}

		return (strpos($path, $cookiePath) === 0);
	}
}
