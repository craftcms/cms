<?php
namespace Craft;

/**
 * Class UrlHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class UrlHelper
{
	// Properties
	// =========================================================================

	private static $_x;

	// Public Methods
	// =========================================================================

	/**
	 * Returns whether a given string appears to be an absolute URL.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function isAbsoluteUrl($url)
	{
		return (strncmp('http://', $url, 7) === 0 || strncmp('https://', $url, 8) === 0);
	}

	/**
	 * Returns whether a given string appears to be a protocol-relative URL.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function isProtocolRelativeUrl($url)
	{
		return (strncmp('//', $url, 2) === 0);
	}

	/**
	 * Returns whether a given string appears to be a root-relative URL.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function isRootRelativeUrl($url)
	{
		return (strncmp('/', $url, 1) === 0 && !static::isProtocolRelativeUrl($url));
	}

	/**
	 * Returns whether a given string appears to be a "full" URL (absolute, root-relative or protocol-relative).
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function isFullUrl($url)
	{
		return (static::isAbsoluteUrl($url) || static::isRootRelativeUrl($url) || static::isProtocolRelativeUrl($url));
	}

	/**
	 * Returns a URL with additional query string parameters.
	 *
	 * @param string       $url
	 * @param array|string $params
	 *
	 * @return string
	 */
	public static function getUrlWithParams($url, $params)
	{
		$params = static::_normalizeParams($params, $anchor);

		if ($params)
		{
			if (mb_strpos($url, '?') !== false)
			{
				$url .= '&';
			}
			else
			{
				$url .= '?';
			}

			$url .= $params;
		}

		if ($anchor)
		{
			$url .= $anchor;
		}

		return $url;
	}

	/**
	 * Returns a URL with a 'token' query string param set to a given token.
	 *
	 * @param string $url
	 * @param string $token
	 *
	 * @return string
	 */
	public static function getUrlWithToken($url, $token)
	{
		return static::getUrlWithParams($url, array(
			craft()->config->get('tokenParam') => $token
		));
	}

	/**
	 * Returns a URL with a specific protocol.
	 *
	 * @param string $url
	 * @param string $protocol
	 *
	 * @return string
	 */
	public static function getUrlWithProtocol($url, $protocol)
	{
		if (!$url || !$protocol)
		{
			return $url;
		}

		if (static::isProtocolRelativeUrl($url))
		{
			return $protocol.':'.$url;
		}
		else if (static::isRootRelativeUrl($url))
		{
			return craft()->request->getHostInfo($protocol).$url;
		}
		else
		{
			return preg_replace('/^https?:/', $protocol.':', $url);
		}
	}

	/**
	 * Returns either a CP or a site URL, depending on the request type.
	 *
	 * @param string            $path
	 * @param array|string|null $params
	 * @param string|null       $protocol
	 * @param bool              $mustShowScriptName
	 *
	 * @return string
	 */
	public static function getUrl($path = '', $params = null, $protocol = null, $mustShowScriptName = false)
	{
		// Return $path if it appears to be an absolute URL.
		if (static::isFullUrl($path))
		{
			if ($params)
			{
				$path = static::getUrlWithParams($path, $params);
			}

			if ($protocol)
			{
				$path = static::getUrlWithProtocol($path, $protocol);
			}

			return $path;
		}

		$path = trim($path, '/');

		if (craft()->request->isCpRequest())
		{
			$path = craft()->config->get('cpTrigger').($path ? '/'.$path : '');
			$cpUrl = true;
		}
		else
		{
			$cpUrl = false;
		}

		// Send all resources over SSL if this request is loaded over SSL.
		if (!$protocol && craft()->request->isSecureConnection())
		{
			$protocol = 'https';
		}

		return static::_getUrl($path, $params, $protocol, $cpUrl, $mustShowScriptName);
	}

	/**
	 * Returns a CP URL.
	 *
	 * @param string            $path
	 * @param array|string|null $params
	 * @param string|null       $protocol
	 *
	 * @return string
	 */
	public static function getCpUrl($path = '', $params = null, $protocol = null)
	{
		$path = trim($path, '/');
		$path = craft()->config->get('cpTrigger').($path ? '/'.$path : '');

		return static::_getUrl($path, $params, $protocol, true, false);
	}

	/**
	 * Returns a site URL.
	 *
	 * @param string $path
	 * @param array|string|null $params
	 * @param string|null $protocol
	 * @param string|null $localeId
	 *
	 * @return string
	 */
	public static function getSiteUrl($path = '', $params = null, $protocol = null, $localeId = null)
	{
		$useLocaleSiteUrl = (
			$localeId !== null &&
			($localeId != craft()->language) &&
			($localeSiteUrl = craft()->config->getLocalized('siteUrl', $localeId))
		);

		if ($useLocaleSiteUrl)
		{
			// Temporarily set Craft to use this element's locale's site URL
			$siteUrl = craft()->getSiteUrl();
			craft()->setSiteUrl($localeSiteUrl);
		}

		$path = trim($path, '/');
		$url = static::_getUrl($path, $params, $protocol, false, false);

		if ($useLocaleSiteUrl)
		{
			craft()->setSiteUrl($siteUrl);
		}

		return $url;
	}

	/**
	 * Returns a resource URL.
	 *
	 * @param string            $path
	 * @param array|string|null $params
	 * @param string|null       $protocol The protocol to use (e.g. http, https). If empty, the protocol used for the
	 *                                    current request will be used.
	 *
	 * @return string
	 */
	public static function getResourceUrl($path = '', $params = null, $protocol = null)
	{
		$path = trim($path, '/');

		if ($path)
		{
			// If we've served this resource before, we should have a cached copy of the server path already. Use that
			// to get its timestamp, and add timestamp to the resource URL so ResourcesService sends it with
			// a Pragma: Cache header.
			$dateParam = craft()->resources->dateParam;

			if (!isset($params[$dateParam]))
			{
				$realPath = craft()->resources->getCachedResourcePath($path);

				if ($realPath)
				{
					if (!is_array($params))
					{
						$params = array($params);
					}

					$timeModified = IOHelper::getLastTimeModified($realPath);
					$params[$dateParam] = $timeModified->getTimestamp();
				}
				else
				{
					// Just set a random query string param on there, so even if the browser decides to cache it,
					// the next time this happens, the cache won't be used.

					// Use a consistent param for all resource requests with uncached paths, in case the same resource
					// URL is requested multiple times in the same request
					if (!isset(static::$_x))
					{
						static::$_x = StringHelper::randomString(9);
					}

					$params['x'] = static::$_x;
				}
			}
		}

		return static::getUrl(craft()->config->getResourceTrigger().'/'.$path, $params, $protocol);
	}

	/**
	 * @param string $path
	 * @param null   $params
	 * @param string $protocol The protocol to use (e.g. http, https). If empty, the protocol used for the current
	 *                         request will be used.
	 *
	 * @return array|string
	 */
	public static function getActionUrl($path = '', $params = null, $protocol = null)
	{
		$path = craft()->config->get('actionTrigger').'/'.trim($path, '/');

		return static::getUrl($path, $params, $protocol, true);
	}

	/**
	 * Removes the query string from a given URL.
	 *
	 * @param $url The URL to check.
	 *
	 * @return string The URL without a query string.
	 */
	public static function stripQueryString($url)
	{
		if (($qIndex = mb_strpos($url, '?')) !== false)
		{
			$url = mb_substr($url, 0, $qIndex);
		}

		// Just in case the URL had an invalid query string
		if (($qIndex = mb_strpos($url, '&')) !== false)
		{
			$url = mb_substr($url, 0, $qIndex);
		}

		return $url;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a URL.
	 *
	 * @param string       $path
	 * @param array|string $params
	 * @param              $protocol
	 * @param              $cpUrl
	 * @param              $mustShowScriptName
	 *
	 * @return string
	 */
	private static function _getUrl($path, $params, $protocol, $cpUrl, $mustShowScriptName)
	{
		// Normalize the params
		$params = static::_normalizeParams($params, $anchor);

		// Were there already any query string params in the path?
		if (($qpos = strpos($path, '?')) !== false)
		{
			$params = substr($path, $qpos+1).($params ? '&'.$params : '');
			$path = substr($path, 0, $qpos);
		}

		$showScriptName = ($mustShowScriptName || !craft()->config->omitScriptNameInUrls());

		if ($cpUrl)
		{
			// Did they set the base URL manually?
			$baseUrl = craft()->config->get('baseCpUrl');

			if ($baseUrl)
			{
				// Make sure it ends in a slash
				$baseUrl = rtrim($baseUrl, '/').'/';

				if ($protocol)
				{
					// Make sure we're using the right protocol
					$baseUrl = static::getUrlWithProtocol($baseUrl, $protocol);
				}

				// Should we be adding that script name in?
				if ($showScriptName)
				{
					$baseUrl .= craft()->request->getScriptName();
				}
			}
			else
			{
				// Figure it out for ourselves, then
				$baseUrl = craft()->request->getHostInfo($protocol ?: '');

				if ($showScriptName)
				{
					$baseUrl .= craft()->request->getScriptUrl();
				}
				else
				{
					$baseUrl .= craft()->request->getBaseUrl();
				}
			}
		}
		else
		{
			$baseUrl = craft()->getSiteUrl($protocol);

			// Should we be adding that script name in?
			if ($showScriptName)
			{
				$baseUrl .= craft()->request->getScriptName();
			}
		}

		// Put it all together
		if (!$showScriptName || craft()->config->usePathInfo())
		{
			if ($path)
			{
				$url = rtrim($baseUrl, '/').'/'.trim($path, '/');

				if (craft()->request->isSiteRequest() && craft()->config->get('addTrailingSlashesToUrls'))
				{
					$url .= '/';
				}
			}
			else
			{
				$url = $baseUrl;
			}
		}
		else
		{
			$url = $baseUrl;

			if ($path)
			{
				$params = craft()->urlManager->pathParam.'='.$path.($params ? '&'.$params : '');
			}
		}

		if ($params)
		{
			$url .= '?'.$params;
		}

		if ($anchor)
		{
			$url .= $anchor;
		}

		return $url;
	}

	/**
	 * Normalizes query string params.
	 *
	 * @param string|array|null $params
	 * @param string|null       &$anchor
	 *
	 * @return string
	 */
	private static function _normalizeParams($params, &$anchor = '')
	{
		if (is_array($params))
		{
			foreach ($params as $name => $value)
			{
				if (!is_numeric($name))
				{
					if ($name == '#')
					{
						$anchor = '#'.$value;
					}
					else if ($value !== null && $value !== '')
					{
						$params[] = $name.'='.$value;
					}

					unset($params[$name]);
				}
			}

			$params = implode('&', array_filter($params));
		}
		else
		{
			$params = trim($params, '&?');
		}

		return $params;
	}
}
