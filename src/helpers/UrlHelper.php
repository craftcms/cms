<?php
namespace Craft;

/**
 *
 */
class UrlHelper
{
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
		$path = $origPath = trim($path, '/');
		$path = craft()->config->get('resourceTrigger').'/'.$path;

		// Add timestamp to the resource URL for caching, if Craft is not operating in dev mode
		if ($origPath && !craft()->config->get('devMode'))
		{
			$realPath = craft()->resources->getResourcePath($origPath);

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

		return static::getUrl($path, $params, $protocol);
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
			$params = ltrim($params, '&?');
		}

		$showScriptName = ($mustShowScriptName || !craft()->config->omitScriptNameInUrls());

		if ($dynamicBaseUrl)
		{
			// Get the full URL to the current script (http://example.com/index.php)
			$baseUrl = craft()->request->getHostInfo($protocol).craft()->urlManager->getBaseUrl();

			// Are we omitting the script name?
			if (!$showScriptName)
			{
				// Chop it off (http://example.com/)
				$baseUrl = mb_substr($baseUrl, 0, mb_strrpos($baseUrl, '/')+1);
			}
		}
		else
		{
			$baseUrl = Craft::getSiteUrl($protocol);

			// Should we be adding that script name in?
			if ($showScriptName)
			{
				$dynamicBaseUrl = craft()->urlManager->getBaseUrl();
				$baseUrl .= mb_strrichr($dynamicBaseUrl, mb_strrpos($dynamicBaseUrl, '/')+1);
			}
		}

		// Put it all together
		if (!$showScriptName || craft()->config->usePathInfo())
		{
			return ($path ? rtrim($baseUrl, '/').'/'.$path : $baseUrl).($params ? '?'.$params : '').$anchor;
		}
		else
		{
			$pathParam = craft()->urlManager->pathParam;
			return $baseUrl.($path || $params ? '?'.($path ? $pathParam.'='.$path : '').($path && $params ? '&' : '').$params : '').$anchor;
		}
	}
}
