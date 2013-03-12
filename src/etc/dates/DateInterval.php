<?php
namespace Craft;

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
		$timeComponents = array();

		if ($this->y)
		{
			$timeComponents[] = $this->y.' '.($this->y > 1 ? Craft::t('years') : Craft::t('year'));
		}

		if ($this->m)
		{
			$timeComponents[] = $this->m.' '.($this->m > 1 ? Craft::t('months') : Craft::t('month'));
		}

		if ($this->d)
		{
			$timeComponents[] = $this->d.' '.($this->d > 1 ? Craft::t('days') : Craft::t('day'));
		}

		if ($this->h)
		{
			$timeComponents[] = $this->h.' '.($this->h > 1 ? Craft::t('hours') : Craft::t('hour'));
		}

		$minutes = $this->i;

		if (!$showSeconds)
		{
			if ($minutes && round($this->s / 60))
			{
				$minutes++;
			}
			else if (!$this->y && !$this->m && !$this->d && !$this->h && !$minutes)
			{
				return Craft::t('less than a minute');
			}
		}

		if ($minutes)
		{
			$timeComponents[] = $minutes.' '.($minutes > 1 ? Craft::t('minutes') : Craft::t('minute'));
		}

		if ($showSeconds && $this->s)
		{
			$timeComponents[] = $this->s.' '.($this->s > 1 ? Craft::t('seconds') : Craft::t('second'));
		}

		return implode(', ', $timeComponents);
	}
}
