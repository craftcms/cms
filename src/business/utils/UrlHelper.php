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
		$path = Blocks::app()->getConfig('resourceTriggerWord').'/'.trim($path, '/');
		$path = self::_normalizePath($path, $params);
		return  Blocks::app()->request->getHostInfo($protocol).HtmlHelper::normalizeUrl($path);
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
		$path = Blocks::app()->getConfig('actionTriggerWord').'/'.trim($path, '/');
		$path = self::_normalizePath($path, $params);
		return Blocks::app()->request->getHostInfo($protocol).HtmlHelper::normalizeUrl($path);
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
		$path = self::_normalizePath(trim($path, '/'), $params);
		return  Blocks::app()->request->getHostInfo($protocol).HtmlHelper::normalizeUrl($path);
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
			$paramsStr = '';
			foreach ($params as $paramName => $paramValue)
			{
				$paramsStr .= '&'.$paramName.'='.$paramValue;
			}
			$params = $paramsStr;
		}

		if (is_string($params))
		{
			$params = ltrim($params, '?&');

			if (Blocks::app()->request->urlFormat == UrlFormat::PathInfo)
				return array($path.'?'.$params);

			return array($path.'&'.$params);
		}

		return array($path);

	}
}
