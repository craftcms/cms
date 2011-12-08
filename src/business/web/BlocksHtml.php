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

	public static function controllerLink($text, $controller, $action, $params = array())
	{
		return self::link($text, array_merge(array('Blocks', 'c' => $controller, 'a' => $action), $params));
	}

	public static function controllerUrl($controller, $action)
	{
		return self::normalizeUrl(array('/', 'c' => $controller, 'a' => $action));
	}

	public static function link($text, $url = '#', $htmlOptions = array())
	{
		if ($url !== '')
			$htmlOptions['href'] = self::normalizeUrl($url);

		self::clientChange('click', $htmlOptions);
		return self::tag('a', $htmlOptions, $text);
	}

	public static function normalizeUrl($url)
	{
		if (is_array($url))
		{
			if (isset($url[0]))
			{
				if ($url[0] == 'Blocks')
					$url[0] = null;

				if (($c = Blocks::app()->getController()) !== null && $c->id !== 'blocks')
					$url = $c->createUrl($url[0], array_splice($url, 1));
				else
					$url = Blocks::app()->createUrl($url[0], array_splice($url, 1));
			}
			else
				$url = '';
		}
		return $url === '' ? Blocks::app()->getRequest()->getUrl() : $url;
	}
}
