<?php

/**
 *
 */
class bUrlHelper
{
	/**
	 * Get the URL to a resource that's located in either blocks/app/resources or a plugin's resources folder
	 * @param string $resourcePath The path to the resource
	 * @param null   $params
	 * @param string $protocol protocol to use (e.g. http, https). If empty, the protocol used for the current request will be used.
	 * @return string The URL to the resource, via Blocks' resource server
	 */
	public static function generateResourceUrl($path = '', $params = null, $protocol = '')
	{
		$path = Blocks::app()->getConfig('resourceTriggerWord').'/'.trim($path, '/');
		$path = self::_normalizePath($path, $params);
		return  Blocks::app()->request->getHostInfo($protocol).bHtml::normalizeUrl($path);
	}

	/**
	 * @static
	 * @param      $actionPath
	 * @param null $params
	 * @param string $protocol protocol to use (e.g. http, https). If empty, the protocol used for the current request will be used.
	 * @return array|string
	 */
	public static function generateActionUrl($path = '', $params = null, $protocol = '')
	{
		$path = Blocks::app()->getConfig('actionTriggerWord').'/'.trim($path, '/');
		$path = self::_normalizePath($path, $params);
		return Blocks::app()->request->getHostInfo($protocol).bHtml::normalizeUrl($path);
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
		return  Blocks::app()->request->getHostInfo($protocol).bHtml::normalizeUrl($path);
	}

	/**
	 * @static
	 * @param        $path
	 * @param        $params
	 * @param string $triggerWord
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

			if (Blocks::app()->request->urlFormat == bUrlFormat::PathInfo)
				return array($path.'?'.$params);

			return array($path.'&'.$params);
		}

		return array($path);

	}
}
