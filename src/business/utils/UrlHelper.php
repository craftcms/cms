<?php
namespace Blocks;

/**
 *
 */
class UrlHelper
{
	/**
	 * Get the URL to a resource that's located in either blocks/app/resources or a plugin's resources folder
	 *
	 * @static
	 * @param string $path
	 * @param null   $params
	 * @param string $protocol protocol to use (e.g. http, https). If empty, the protocol used for the current request will be used.
	 * @return string The URL to the resource, via Blocks' resource server
	 */
	public static function generateResourceUrl($path = '', $params = null, $protocol = '')
	{
		$origPath = $path;
		$path = b()->config->getItem('resourceTriggerWord').'/'.trim($path, '/');
		$path = self::_normalizePath($path, $params);
		$path = b()->request->getHostInfo($protocol).HtmlHelper::normalizeUrl($path);
		return $origPath == '' ? $path.'/' : $path;
	}

	/**
	 * @static
	 * @param string $path
	 * @param null   $params
	 * @param string $protocol protocol to use (e.g. http, https). If empty, the protocol used for the current request will be used.
	 * @return array|string
	 */
	public static function generateActionUrl($path = '', $params = null, $protocol = '')
	{
		$origPath = $path;
		$path = b()->config->getItem('actionTriggerWord').'/'.trim($path, '/');
		$path = self::_normalizePath($path, $params);
		$path = b()->request->getHostInfo($protocol).HtmlHelper::normalizeUrl($path);
		return $origPath == '' ? $path.'/' : $path;
	}

	/**
	 * @static
	 * @param      $path
	 * @param null $params
	 * @param string $protocol protocol to use (e.g. http, https). If empty, the protocol used for the current request will be used.
	 * @return array|string
	 */
	public static function generateUrl($path = '', $params = null, $protocol = '')
	{
		$origPath = $path;
		$routeVar = b()->urlManager->routeVar;

		$path = self::_normalizePath(trim($path, '/'), $params);
		$path = b()->request->getHostInfo($protocol).HtmlHelper::normalizeUrl($path);

		if (b()->request->urlFormat == UrlFormat::PathInfo && $params == null)
			$path = $origPath == '' ? $path.'/' : $path;
		else
		{
			// stupid way of checking if p doesn't have a value set in the given path.
			if (($pos = strpos($path, $routeVar.'=')) !== false && isset($path[$pos+2]) && $path[$pos+2] == '&')
			{
				if ($params == null)
					$search = $routeVar.'=';
				else
					$search = $routeVar.'=&';

				$path = str_replace($search, '', $path);
			}
			else
			{
				if (strpos($path, $routeVar.'=') === false)
					$path = $path.'?'.$routeVar.'=';
			}
		}

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
			return array_merge(array($path), $params);

		if (is_string($params))
		{
			$params = ltrim($params, '?&');

			if (b()->request->urlFormat == UrlFormat::PathInfo)
				return array($path.'?'.$params);

			return array($path.'&'.$params);
		}

		return array($path);

	}
}
