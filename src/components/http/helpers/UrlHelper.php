<?php
namespace Blocks;

/**
 *
 */
class UrlHelper
{
	/**
	 * @static
	 * @param      $path
	 * @param null $params
	 * @param string $protocol protocol to use (e.g. http, https). If empty, the protocol used for the current request will be used.
	 * @return array|string
	 */
	public static function getUrl($path = '', $params = null, $protocol = '')
	{
		// Return $path if it appears to be an absolute URL.
		if (strpos($path, '://') !== false)
		{
			return $path;
		}

		// Get the base URL
		if (blx()->request->getType() == HttpRequestType::Site)
		{
			$baseUrl = Blocks::getSiteUrl();
		}
		else
		{
			$baseUrl = blx()->request->getHostInfo($protocol).blx()->urlManager->getBaseUrl();
		}

		$baseUrl = rtrim($baseUrl, '/');
		$path = trim($path, '/');

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

		// Put it all together
		if (blx()->request->getUrlFormat() == UrlFormat::PathInfo)
		{
			return $baseUrl.($path ? '/'.$path : '').($params ? '?'.$params : '').$anchor;
		}
		else
		{
			$pathParam = blx()->urlManager->pathParam;
			return $baseUrl.($path || $params ? '?'.($path ? $pathParam.'='.$path : '').($path && $params ? '&' : '').$params : '').$anchor;
		}
	}

	/**
	 * Get the URL to a resource that's located in either blocks/app/resources or a plugin's resources folder
	 *
	 * @static
	 * @param string $path
	 * @param null   $params
	 * @param string $protocol protocol to use (e.g. http, https). If empty, the protocol used for the current request will be used.
	 * @return string The URL to the resource, via Blocks' resource server
	 */
	public static function getResourceUrl($path = '', $params = null, $protocol = '')
	{
		$path = $origPath = trim($path, '/');
		$path = blx()->config->resourceTrigger.'/'.$path;

		// Add timestamp to the resource URL for caching, if Blocks is not operating in dev mode
		if ($origPath && !blx()->config->devMode)
		{
			$realPath = blx()->resources->getResourcePath($origPath);

			if ($realPath)
			{
				if (!is_array($params))
				{
					$params = array($params);
				}

				$dateParam = blx()->resources->dateParam;
				$timeModified = filemtime($realPath);
				$params[$dateParam] = $timeModified;
			}
		}

		$path = static::getUrl($path, $params, $protocol);
		$path = $origPath == '' ? $path.'/' : $path;

		return $path;
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
		$origPath = $path;
		$path = blx()->config->actionTrigger.'/'.trim($path, '/');
		$path = static::getUrl($path, $params, $protocol);
		$path = $origPath == '' ? $path.'/' : $path;

		return $path;
	}
}
