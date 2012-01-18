<?php
/**
 * Modified version of CookieJar of Zend
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
 * A HttpCookieJar object is designed to contain and maintain HTTP cookies, and should
 * be used along with HttpClient in order to manage cookies across HTTP requests and
 * responses.
 *
 * The class contains an array of HttpCookie objects. Cookies can be added to the jar
 * automatically from a request or manually. Then, the jar can find and return the cookies
 * needed for a specific HTTP request.
 *
 * A special parameter can be passed to all methods of this class that return cookies: Cookies
 * can be returned either in their native form (as HttpCookie objects) or as strings -
 * the later is suitable for sending as the value of the "Cookie" header in an HTTP request.
 * You can also choose, when returning more than one cookie, whether to get an array of strings
 * (by passing HttpCookie::COOKIE_STRING_ARRAY) or one unified string for all cookies
 * (by passing HttpCookie::COOKIE_STRING_CONCAT).
 *
 * @link       http://wp.netscape.com/newsref/std/cookie_spec.html for some specs.
 */

class CookieJar
{
	/**
	 * Return cookie(s) as a HttpCookie object
	 */
	const COOKIE_OBJECT = 0;

	/**
	 * Return cookie(s) as a string (suitable for sending in an HTTP request)
	 */
	const COOKIE_STRING_ARRAY = 1;

	/**
	 * Return all cookies as one long string (suitable for sending in an HTTP request)
	 */
	const COOKIE_STRING_CONCAT = 2;

	/**
	 * Array storing cookies
	 * Cookies are stored according to domain and path:
	 * $cookies
	 *  + www.mydomain.com
	 *    + /
	 *      - cookie1
	 *      - cookie2
	 *    + /somepath
	 *      - othercookie
	 *  + www.otherdomain.net
	 *    + /
	 *      - alsocookie
	 * @var array
	 */
	protected $cookies = array();

	/**
	 * The HttpCookie array
	 * @var array
	 */
	protected $_rawCookies = array();

	/**
	 * Construct a new CookieJar object
	 */
	public function __construct()
	{ }

	/**
	 * Add a cookie to the jar. Cookie should be passed either as a HttpCookie object
	 * or as a string - in which case an object is created from the string.
	 * @param HttpCookie|string $cookie
	 * @param UriHttp|string    $ref_uri Optional reference URI (for domain, path, secure)
	 * @param boolean $encodeValue
	 */
	public function addCookie($cookie, $ref_uri = null, $encodeValue = true)
	{
		if (is_string($cookie))
		{
			$cookie = HttpCookie::fromString($cookie, $ref_uri, $encodeValue);
		}

		if ($cookie instanceof HttpCookie)
		{
			$domain = $cookie->getDomain();
			$path = $cookie->getPath();

			if (!isset($this->cookies[$domain]))
				$this->cookies[$domain] = array();

			if (!isset($this->cookies[$domain][$path]))
				$this->cookies[$domain][$path] = array();

			$this->cookies[$domain][$path][$cookie->getName()] = $cookie;
			$this->_rawCookies[] = $cookie;
		}
		else
		{
			throw new bException('Argument is not a valid cookie string or object');
		}
	}

	/**
	 * Parse an HTTP response, adding all the cookies set in that response
	 * to the cookie jar.
	 * @param HttpResponse $response
	 * @param UriHttp|string $ref_uri Requested URI
	 * @param boolean $encodeValue
	 */
	public function addCookiesFromResponse($response, $ref_uri, $encodeValue = true)
	{
		if (!$response instanceof HttpResponse)
		{
			throw new bException('$response is expected to be a Response object, '.gettype($response).' was passed');
		}

		$cookie_hdrs = $response->getHeader('Set-Cookie');

		if (is_array($cookie_hdrs))
		{
			foreach ($cookie_hdrs as $cookie)
			{
				$this->addCookie($cookie, $ref_uri, $encodeValue);
			}
		}
		else
		{
			if (is_string($cookie_hdrs))
			{
				$this->addCookie($cookie_hdrs, $ref_uri, $encodeValue);
			}
		}
	}

	/**
	 * Get all cookies in the cookie jar as an array
	 * @param int $ret_as Whether to return cookies as objects of HttpCookie or as strings
	 * @return array|string
	 */
	public function getAllCookies($ret_as = self::COOKIE_OBJECT)
	{
		$cookies = $this->_flattenCookiesArray($this->cookies, $ret_as);
		return $cookies;
	}

	/**
	 * Return an array of all cookies matching a specific request according to the request URI,
	 * whether session cookies should be sent or not, and the time to consider as "now" when
	 * checking cookie expiry time.
	 * @param string|UriHttp $uri URI to check against (secure, domain, path)
	 * @param boolean $matchSessionCookies Whether to send session cookies
	 * @param int $ret_as Whether to return cookies as objects of HttpCookie or as strings
	 * @param int $now Override the current time when checking for expiry time
	 * @return array|string
	 */
	public function getMatchingCookies($uri, $matchSessionCookies = true, $ret_as = self::COOKIE_OBJECT, $now = null)
	{
		if (is_string($uri))
			$uri = Uri::factory($uri);

		if (!$uri instanceof UriHttp)
		{
			throw new bException("Invalid URI string or object passed");
		}

		// First, reduce the array of cookies to only those matching domain and path
		$cookies = $this->_matchDomain($uri->getHost());
		$cookies = $this->_matchPath($cookies, $uri->getPath());
		$cookies = $this->_flattenCookiesArray($cookies, self::COOKIE_OBJECT);

		// Next, run Cookie->match on all cookies to check secure, time and session matching
		$ret = array();

		foreach ($cookies as $cookie)
		{
			if ($cookie->match($uri, $matchSessionCookies, $now))
			{
				$ret[] = $cookie;
			}
		}

		// Now, use self::_flattenCookiesArray again - only to convert to the return format
		$ret = $this->_flattenCookiesArray($ret, $ret_as);

		return $ret;
	}

	/**
	 * Get a specific cookie according to a URI and name
	 * @param UriHttp|string $uri The uri (domain and path) to match
	 * @param string $cookie_name The cookie's name
	 * @param int $ret_as Whether to return cookies as objects of HttpCookie or as strings
	 * @return HttpCookie|string
	 */
	public function getCookie($uri, $cookie_name, $ret_as = self::COOKIE_OBJECT)
	{
		if (is_string($uri))
		{
			$uri = Uri::factory($uri);
		}

		if (!$uri instanceof UriHttp)
		{
			throw new CException('Invalid URI specified');
		}

		// Get correct cookie path
		$path = $uri->getPath();
		$path = substr($path, 0, strrpos($path, '/'));

		if (!$path)
			$path = '/';

		if (isset($this->cookies[$uri->getHost()][$path][$cookie_name]))
		{
			$cookie = $this->cookies[$uri->getHost()][$path][$cookie_name];

			switch ($ret_as)
			{
				case self::COOKIE_OBJECT:
					return $cookie;
					break;

				case self::COOKIE_STRING_ARRAY:
				case self::COOKIE_STRING_CONCAT:
					return $cookie->__toString();
					break;

				default:
					throw new bException("Invalid value passed for \$ret_as: {$ret_as}");
					break;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Helper function to recursively flatten an array. Should be used when exporting the cookies array (or parts of it)
	 * @param HttpCookie|array $ptr
	 * @param int $ret_as What value to return
	 * @return array|string
	 */
	protected function _flattenCookiesArray($ptr, $ret_as = self::COOKIE_OBJECT)
	{
		if (is_array($ptr))
		{
			$ret = ($ret_as == self::COOKIE_STRING_CONCAT ? '' : array());
			foreach ($ptr as $item)
			{
				if ($ret_as == self::COOKIE_STRING_CONCAT)
				{
					$ret .= $this->_flattenCookiesArray($item, $ret_as);
				}
				else
				{
					$ret = array_merge($ret, $this->_flattenCookiesArray($item, $ret_as));
				}
			}

			return $ret;
		}
		else
		{
			if ($ptr instanceof HttpCookie)
			{
				switch ($ret_as)
				{
					case self::COOKIE_STRING_ARRAY:
						return array($ptr->__toString());
						break;

					case self::COOKIE_STRING_CONCAT:
						return $ptr->__toString();
						break;

					case self::COOKIE_OBJECT:
					default:
						return array($ptr);
						break;
				}
			}
		}

		return null;
	}

	/**
	 * Return a subset of the cookies array matching a specific domain
	 * Returned array is actually an array of pointers to items in the $this->cookies array.
	 * @param string $domain
	 * @return array
	 */
	protected function _matchDomain($domain)
	{
		$ret = array();

		foreach (array_keys($this->cookies) as $cdom)
		{
			if(HttpCookie::matchCookieDomain($cdom, $domain))
			{
				$ret[$cdom] = $this->cookies[$cdom];
			}
		}

		return $ret;
	}

	/**
	 * Return a subset of a domain-matching cookies that also match a specified path
	 * @param $domains
	 * @param string $path
	 * @return array
	 */
	protected function _matchPath($domains, $path)
	{
		$ret = array();

		foreach ($domains as $dom => $paths_array)
		{
			foreach (array_keys($paths_array) as $cpath)
			{
				if (HttpCookie::matchCookiePath($cpath, $path))
				{
					if (! isset($ret[$dom]))
					{
						$ret[$dom] = array();
					}

					$ret[$dom][$cpath] = $paths_array[$cpath];
				}
			}
		}

		return $ret;
	}

	/**
	 * Create a new CookieJar object and automatically load into it all the
	 * cookies set in an Http_Response object. If $uri is set, it will be
	 * considered as the requested URI for setting default domain and path
	 * of the cookie.
	 * @param HttpResponse $response HTTP Response object
	 * @param UriHttp|string $ref_uri The requested URI
	 * @return HttpCookieJar
	 * @todo Add the $uri functionality.
	 */
	public static function fromResponse(HttpResponse $response, $ref_uri)
	{
		$jar = new self();
		$jar->addCookiesFromResponse($response, $ref_uri);
		return $jar;
	}

	/**
	 * Required by Countable interface
	 * @return int
	 */
	public function count()
	{
		return count($this->_rawCookies);
	}

	/**
	 * Required by IteratorAggregate interface
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->_rawCookies);
	}

	/**
	 * Tells if the jar is empty of any cookie
	 * @return bool
	 */
	public function isEmpty()
	{
		return count($this) == 0;
	}

	/**
	 * Empties the cookieJar of any cookie
	 * @return CookieJar
	 */
	public function reset()
	{
		$this->cookies = $this->_rawCookies = array();
		return $this;
	}
}
