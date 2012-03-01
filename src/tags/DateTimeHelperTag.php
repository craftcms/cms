<?php
namespace Blocks;

/**
 *
 */
class DateTimeHelperTag extends Tag
{
	/**
	 * @param $seconds
	 * @return int
	 */
	public function secondsToHumanTimeDuration($seconds)
	{
		return DateTimeHelper::secondsToHumanTimeDuration($seconds);
	}

	/**
	 * @param $dateString
	 * @return string
	 */
	public function nice($dateString)
	{
		return DateTimeHelper::nice($dateString);
	}
}
