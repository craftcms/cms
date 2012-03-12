<?php
namespace Blocks;

/**
 *
 */
class DateTimeHelperTag
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
