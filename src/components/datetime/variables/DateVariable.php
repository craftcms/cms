<?php
namespace Blocks;

/**
 * Date functions
 */
class DateVariable
{
	private $dateTimeVariable;

	/**
	 * Returns a given number of seconds in a more meaningful format.
	 *
	 * @param int $seconds
	 * @return string
	 */
	public function secondsToHumanTimeDuration($seconds)
	{
		return DateTimeHelper::secondsToHumanTimeDuration($seconds);
	}

	/**
	 * @param $dateTime
	 * @return string
	 */
	public function nice(DateTime $dateTime)
	{
		return DateTimeHelper::nice($dateTime->getTimestamp());
	}
}
