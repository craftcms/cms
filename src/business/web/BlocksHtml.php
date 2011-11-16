<?php

class BlocksHtml extends CHtml
{
	/**
	 * Get the URL to a resource that's located in either blocks/app/resources or a plugin's resources folder
	 * @param string $resourcePath The path to the resource
	 * @param string $plugin The plugin name (optional)
	 * @return string The URL to the resource, via Blocks' resource server
	 */
	public static function getResourceUrl($resourcePath, $plugin = null)
	{
		$baseUrl = Blocks::app()->getUrlManager()->getBaseUrl();
		$where = $plugin ? $plugin : 'app';

		return "{$baseUrl}/resources/{$where}/".self::encode($resourcePath);
	}

	public static function unixTimeToPrettyDate($unixTime)
	{
		return Blocks::app()->dateFormatter->format('MM-dd-yyyy HH:mm:ss', $unixTime);
	}
}
