<?php
namespace Blocks;

/**
 *
 */
class DateTimeHelper
{
	public static function getCurrentUnixTimeStamp()
	{
		$date = new \DateTime();
		return $date->getTimestamp();
	}
}
