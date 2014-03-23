<?php
namespace Craft;

/**
 *
 */
class UrlHelper
{
	/**
	 * Returns a URL with a specific protocol.
	 *
	 * @param string $url
	 * @param string $protocol
	 * @return string
	 */
	public static function getUrlWithProtocol($url, $protocol)
	{
		if (!$url || !$protocol)
		{
			return $url;
		}

		// Is the site URL protocol relative?
		if (strncmp('//', $url, 2) === 0)
		{
			return $protocol.':'.$url;
		}

		// Is it root relative?
		if (strncmp('/', $url, 1) === 0)
		{
			return craft()->request->getHostInfo($protocol).$url;
		}

		return preg_replace('/^https?:/', $protocol.':', $url);
	}

	/**
	 * Returns either a CP or a site URL, depending on the request type.
	 *
	 * @static
	 * @param string            $path
	 * @param array|string|null $params
	 * @param string|null       $protocol
	 * @param bool              $mustShowScriptName
	 * @return string
	 */
	public static function getUrl($path = '', $params = null, $protocol = '', $mustShowScriptName = false)
	{
		// Return $path if it appears to be an absolute URL.
		if (mb_strpos($path, '://') !== false || strncmp($path, '//', 2) == 0)
		{
			return $path;
		}

		$path = trim($path, '/');

		if (craft()->request->isCpRequest())
		{
			$path = craft()->config->get('cpTrigger').($path ? '/'.$path : '');
			$dynamicBaseUrl = true;
		}
		else
		{
			$dynamicBaseUrl = false;
		}

		// Send all resources over SSL if this request is loaded over SSL.
		if ($protocol === '' && craft()->request->isSecureConnection())
		{
			$protocol = 'https';
		}

		return static::_getUrl($path, $params, $protocol, $dynamicBaseUrl, $mustShowScriptName);
	}

	/**
	 * Returns a CP URL.
	 *
	 * @static
	 * @param string $path
	 * @param array|string|null $params
	 * @param string|null $protocol
	 * @return string
	 */
	public static function getCpUrl($path = '', $params = null, $protocol = '')
	{
		$path = trim($path, '/');
		$path = craft()->config->get('cpTrigger').($path ? '/'.$path : '');

		return static::_getUrl($path, $params, $protocol, true, false);
	}

	/**
	 * Returns a site URL.
	 *
	 * @static
	 * @param string $path
	 * @param array|string|null $params
	 * @param string|null $protocol
	 * @return string
	 */
	public static function getSiteUrl($path = '', $params = null, $protocol = '')
	{
		$path = trim($path, '/');
		return static::_getUrl($path, $params, $protocol, false, false);
	}

	/**
	 * Returns a resource URL.
	 *
	 * @static
	 * @param string $path
	 * @param array|string|null $params
	 * @param string|null $protocol protocol to use (e.g. http, https). If empty, the protocol used for the current request will be used.
	 * @return string
	 */
	public static function getResourceUrl($path = '', $params = null, $protocol = '')
	{
		$path = trim($path, '/');

		if ($path)
		{
			// If we've served this resource before, we should have a cached copy of the server path already.
			// Use that to get its timestamp, and add timestamp to the resource URL so ResourcesService sends it with a Pragma: Cache header.

			$realPath = craft()->resources->getCachedResourcePath($path);

			if ($realPath)
			{
				if (!is_array($params))
				{
					$params = array($params);
				}

				$dateParam = craft()->resources->dateParam;
				$timeModified = IOHelper::getLastTimeModified($realPath);
				$params[$dateParam] = $timeModified->getTimestamp();
			}
		}

		return static::getUrl(craft()->config->getResourceTrigger().'/'.$path, $params, $protocol);
	}

	/**
	 * @static
	 * @param string $path
	 * @param null   $params
	 * @param string $protocol protocol to use (e.g. http, https). If empty, the protocol used for the current request will be used.
	 * @return array|string
	 */
	public static function getActionUrl($path = '', $params = null, $protocol = '')
	{
		$path = craft()->config->get('actionTrigger').'/'.trim($path, '/');
		return static::getUrl($path, $params, $protocol, true);
	}

	/**
	 * Returns a URL.
	 *
	 * @access private
	 * @param string       $path
	 * @param array|string $params
	 * @param              $protocol
	 * @param              $dynamicBaseUrl
	 * @param              $mustShowScriptName
	 * @return string
	 */
	private static function _getUrl($path, $params, $protocol, $dynamicBaseUrl, $mustShowScriptName)
	{
		$anchor = '';

		// Normalize the params
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

		// Were there already any query string params in the path?
		if (($qpos = strpos($path, '?')) !== false)
		{
			$params = substr($path, $qpos+1).($params ? '&'.$params : '');
			$path = substr($path, 0, $qpos);
		}

		$showScriptName = ($mustShowScriptName || !craft()->config->omitScriptNameInUrls());

		if ($dynamicBaseUrl)
		{
			$baseUrl = craft()->request->getHostInfo($protocol);

			if ($showScriptName)
			{
				$baseUrl .= craft()->request->getScriptUrl();
			}
			else
			{
				$baseUrl .= craft()->request->getBaseUrl();
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
}
