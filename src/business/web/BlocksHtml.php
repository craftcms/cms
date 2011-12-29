<?php

class BlocksHtml extends CHtml
{
	/*public static function normalizeUrl($url)
	{
		if (is_array($url))
		{
			if (isset($url[0]))
			{
				//if ($url[0] == 'Blocks')
				//	$url[0] = null;

				//if (($c = Blocks::app()->getController()) !== null && $c->id !== 'blocks')
				if (($c = Blocks::app()->getController()) !== null)
					$url = $c->createUrl($url[0], array_splice($url, 1));
				else
					$url = Blocks::app()->createUrl($url[0], array_splice($url, 1));
			}
			else
				$url = '';
		}

		return $url === '' ? Blocks::app()->getRequest()->getUrl() : $url;
	}*/

	public static function unixTimeToPrettyDate($unixTime)
	{
		return Blocks::app()->dateFormatter->format('MM-dd-yyyy HH:mm:ss', $unixTime);
	}
}
