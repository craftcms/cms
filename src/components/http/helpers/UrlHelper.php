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

		$origPath = $path;
		$pathVar = blx()->urlManager->routeVar;

		$path = static::_normalizePath(trim($path, '/'), $params);
		$path = blx()->request->getHostInfo($protocol).HtmlHelper::normalizeUrl($path);

		if (blx()->request->getUrlFormat() == UrlFormat::PathInfo && $params == null)
		{
			$path = $origPath == '' ? $path.'/' : $path;
		}
		else
		{
			// stupid way of checking if p doesn't have a value set in the given path.
			if (($pos = strpos($path, $pathVar.'=')) !== false && isset($path[$pos+2]) && $path[$pos+2] == '&')
			{
				if ($params == null)
				{
					$search = $pathVar.'=';
				}
				else
				{
					$search = $pathVar.'=&';
				}

				$path = str_replace($search, '', $path);
			}
			else
			{
				if (strpos($path, $pathVar.'=') === false && blx()->request->getUrlFormat() == UrlFormat::QueryString)
				{
					$path = $path.'?'.$pathVar.'=';
				}
			}
		}

		return $path;
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
		$origPath = $path;
		$path = blx()->config->resourceTrigger.'/'.trim($path, '/');
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

	/**
	 * @static
	 * @param        $path
	 * @param        $params
	 * @return array|string
	 */
	private static function _normalizePath($path, $params)
	{
		$path = '/'.$path;

		if (is_array($params))
		{
			return array_merge(array($path), $params);
		}

		if (is_string($params))
		{
			$params = ltrim($params, '?&');

			if (blx()->request->getUrlFormat() == UrlFormat::PathInfo)
			{
				return array($path.'?'.$params);
			}

			return array($path.'&'.$params);
		}

		return array($path);
	}
}
