<?php

/**
 *
 */
class bHtml extends CHtml
{
	/**
	 * @static
	 * @param $unixTime
	 * @return mixed
	 */
	public static function unixTimeToPrettyDate($unixTime)
	{
		return Blocks::app()->dateFormatter->format('MM-dd-yyyy HH:mm:ss', $unixTime);
	}
}
