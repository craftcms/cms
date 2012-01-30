<?php
namespace Blocks;

/**
 *
 */
class UrlHelper
{
	/**
	 * Get the URL to a resource that's located in either blocks/app/resources or a plugin's resources folder
	 * @param string $resourcePath The path to the resource
	 * @param null   $params
	 * @param string $protocol protocol to use (e.g. http, https). If empty, the protocol used for the current request will be used.
	 * @return string The URL to the resource, via Blocks' resource server
	 */
	public static function generateResourceUrl($resourcePath, $params = null, $protocol = '')
	{
		$path = self::_normalizePath($resourcePath, $params, Blocks::app()->getConfig('resourceTriggerWord'));
		return  Blocks::app()->request->getHostInfo($protocol).HtmlHelper::normalizeUrl($path);
	}

	/**
	 * @static
	 * @param      $actionPath
	 * @param null $params
	 * @param string $protocol protocol to use (e.g. http, https). If empty, the protocol used for the current request will be used.
	 * @return array|string
	 */
	public static function generateActionUrl($actionPath, $params = null, $protocol = '')
	{
		$path = self::_normalizePath($actionPath, $params, Blocks::app()->getConfig('actionTriggerWord'));
		return Blocks::app()->request->getHostInfo($protocol).HtmlHelper::normalizeUrl($path);
	}

	/**
	 * @static
	 * @param      $path
	 * @param null $params
	 * @param string $protocol protocol to use (e.g. http, https). If empty, the protocol used for the current request will be used.
	 * @return array|string
	 */
	public static function generateUrl($path, $params = null, $protocol = '')
	{
		$path = self::_normalizePath($path, $params);
		return  Blocks::app()->request->getHostInfo($protocol).HtmlHelper::normalizeUrl($path);
	}

	/**
	 * @static
	 * @param        $path
	 * @param        $params
	 * @param string $triggerWord
	 * @return array|string
	 */
	private static function _normalizePath($path, $params, $triggerWord = '')
	{
		$path = ltrim($path, '/');
		$pathParts = explode('/', $path);

		$handle = '/'.array_shift($pathParts);
		$path = count($pathParts) == 0 ? '' : '/'.implode('/', $pathParts);
		$pathStr = $triggerWord == '' ? $handle.$path : '/'.$triggerWord.$handle.$path;

		if (is_array($params))
			$path = array_merge(array($pathStr), $params);
		else
		{
			if (is_string($params))
			{
				$params = ltrim($params, '?');
				$params = ltrim($params, '&');

				if (Blocks::app()->request->urlFormat == UrlFormat::PathInfo)
					$path = array($pathStr.'?'.$params);
				else
					$path = array($pathStr.'&'.$params);
			}
			else
				$path = array($pathStr);
		}

		return $path;
	}
}
