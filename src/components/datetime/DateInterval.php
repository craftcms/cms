<?php
namespace Blocks;

/**
 *
 */
class DateInterval extends \DateInterval
{
	/**
	 * Returns the interval in seconds.
	 *
	 * @return int
	 */
	public function seconds()
	{
		return $this->format('%s');
	}

	/**
	 * Returns the interval in a human-friendly string.
	 *
	 * @param bool $showSeconds
	 * @return string
	 */
	public function humanDuration($showSeconds = true)
	{
		return DateTimeHelper::secondsToHumanTimeDuration($this->seconds(), $showSeconds);
	}
}
