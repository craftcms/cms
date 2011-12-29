<?php

class UrlHelper
{
	/**
	 * Get the URL to a resource that's located in either blocks/app/resources or a plugin's resources folder
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

	public static function generateActionUrl($actionPath, $params = null)
	{
		$path = self::_normalizePath($actionPath, $params, Blocks::app()->config('actionTriggerWord'));
		return BlocksHtml::normalizeUrl($path);
	}

	public static function generateUrl($path, $params = null)
	{
		$path = self::_normalizePath($path, $params);
		return BlocksHtml::normalizeUrl($path);
	}

	private static function _normalizePath($path, $params, $triggerWord = '')
	{
		$path = ltrim($path, '/');
		$pathParts = explode('/', $path);

		$handle = '/'.array_shift($pathParts);
		$path = '/'.implode('/', $pathParts);
		$pathStr = '/'.$triggerWord.$handle.$path;

		if (is_array($params))
			$path = array_merge(array($pathStr), $params);
		else
		{
			if (is_string($params))
			{
				$params = ltrim($params, '?');
				$params = ltrim($params, '&');

				$path = array($pathStr.'?'.$params);
			}
			else
				$path = array($pathStr);
		}

		return $path;
	}
}
