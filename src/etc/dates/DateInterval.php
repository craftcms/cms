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
		$timeComponents = array();

		if ($this->y)
		{
			$timeComponents[] = $this->y.' '.($this->y > 1 ? Blocks::t('years') : Blocks::t('year'));
		}

		if ($this->m)
		{
			$timeComponents[] = $this->m.' '.($this->m > 1 ? Blocks::t('months') : Blocks::t('month'));
		}

		if ($this->d)
		{
			$timeComponents[] = $this->d.' '.($this->d > 1 ? Blocks::t('days') : Blocks::t('day'));
		}

		if ($this->h)
		{
			$timeComponents[] = $this->h.' '.($this->h > 1 ? Blocks::t('hours') : Blocks::t('hour'));
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
				return Blocks::t('less than a minute');
			}
		}

		if ($minutes)
		{
			$timeComponents[] = $minutes.' '.($minutes > 1 ? Blocks::t('minutes') : Blocks::t('minute'));
		}

		if ($showSeconds && $this->s)
		{
			$timeComponents[] = $this->s.' '.($this->s > 1 ? Blocks::t('seconds') : Blocks::t('second'));
		}

		return implode(', ', $timeComponents);
	}
}
