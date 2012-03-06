<?php
namespace Blocks;

/**
 *
 */
class HtmlHelper extends \CHtml
{
	/**
	 * @static
	 * @param $unixTime
	 * @return mixed
	 */
	public static function unixTimeToPrettyDate($unixTime)
	{
		return b()->dateFormatter->format('MM-dd-yyyy HH:mm:ss', $unixTime);
	}
}
