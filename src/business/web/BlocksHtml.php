<?php

class BlocksHtml extends CHtml
{
	/**
	 * Get the URL to a resource that's located in either blocks/app/resources or a plugin's resources folder
	 * @param string $resourcePath The path to the resource
	 * @return string The URL to the resource, via Blocks' resource server
	 */
	public static function getResourceUrl($resourcePath)
	{
		$baseUrl = Blocks::app()->urlManager->getBaseUrl();
		return $baseUrl.'/'.'resources/'.self::encode($resourcePath);
	}

	public static function unixTimeToPrettyDate($unixTime)
	{
		return Blocks::app()->dateFormatter->format('MM-dd-yyyy HH:mm:ss', $unixTime);
	}

	public static function controllerLink($text, $controller, $action)
	{
		return parent::link($text, array(null, 'c' => $controller, 'a' => $action));
	}

	public static function controllerUrl($controller, $action)
	{
		return parent::normalizeUrl(array('/', 'c' => $controller, 'a' => $action));
	}
}
