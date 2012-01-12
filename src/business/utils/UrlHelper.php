<?php

/**
 *
 */
class UrlHelper
{
	/**
	 * Get the URL to a resource that's located in either blocks/app/resources or a plugin's resources folder
	 *
	 * @access public
	 *
	 * @param string $resourcePath The path to the resource
	 * @param null   $params
	 *
	 * @return string The URL to the resource, via Blocks' resource server
	 */
	public static function generateResourceUrl($resourcePath, $params = null)
	{
		$path = self::_normalizePath($resourcePath, $params, Blocks::app()->config('resourceTriggerWord'));
		return BlocksHtml::normalizeUrl($path);
	}

	/**
	 * @access public
	 *
	 * @static
	 *
	 * @param      $actionPath
	 * @param null $params
	 *
	 * @return array|string
	 */
	public static function generateActionUrl($actionPath, $params = null)
	{
		$path = self::_normalizePath($actionPath, $params, Blocks::app()->config('actionTriggerWord'));
		return BlocksHtml::normalizeUrl($path);
	}

	/**
	 * @access public
	 *
	 * @static
	 *
	 * @param      $path
	 * @param null $params
	 *
	 * @return array|string
	 */
	public static function generateUrl($path, $params = null)
	{
		$path = self::_normalizePath($path, $params);
		return BlocksHtml::normalizeUrl($path);
	}

	/**
	 * @access public
	 *
	 * @static
	 *
	 * @param        $path
	 * @param        $params
	 * @param string $triggerWord
	 *
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
