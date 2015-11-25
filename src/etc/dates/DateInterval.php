<?php
namespace Craft;

/**
 * Class DateInterval
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.dates
 * @since     1.0
 */
class DateInterval extends \DateInterval
{
	// Constants
	// =========================================================================

	/**
	 * Number of seconds in a minute.
	 *
	 * @var integer
	 */
	const SECONDS_MINUTE = 60;

	/**
	 * Number of seconds in an hour.
	 *
	 * @var integer
	 */
	const SECONDS_HOUR = 3600;

	/**
	 * Number of seconds in a day.
	 *
	 * @var integer
	 */
	const SECONDS_DAY = 86400;

	/**
	 * The number of seconds in a month.
	 *
	 * Based on a 30.4368 day month, with the product rounded.
	 *
	 * @var integer
	 */
	const SECONDS_MONTH = 2629740;

	/**
	 * The number of seconds in a year.
	 *
	 * Based on a 365.2416 day year, with the product rounded.
	 *
	 * @var integer
	 */
	const SECONDS_YEAR = 31556874;

	// Properties
	// =========================================================================

	/**
	 * The date properties.
	 *
	 * @var array
	 */
	private static $_date = array('y' => 'Y', 'm' => 'M', 'd' => 'D');

	/**
	 * The time properties.
	 *
	 * @var array
	 */
	private static $_time = array('h' => 'H', 'i' => 'M', 's' => 'S');

	// Public Methods
	// =========================================================================

	/**
	 * Returns the interval specification.
	 *
	 * @return string The interval specification.
	 */
	public function __toString()
	{
		return self::toSpec($this);
	}

	/**
	 * Returns the DateInterval instance for the number of seconds.
	 *
	 * @param int|string $seconds The number of seconds.
	 *
	 * @return DateInterval The date interval.
	 */
	public static function fromSeconds($seconds)
	{
		$interval = new static('PT0S');
		$seconds = (int)$seconds;

		foreach (array('y' => self::SECONDS_YEAR, 'm' => self::SECONDS_MONTH, 'd' => self::SECONDS_DAY, 'h' => self::SECONDS_HOUR, 'i' => self::SECONDS_MINUTE) as $property => $increment)
		{
			$increment = (int)$increment;

			if ($seconds === $increment || $seconds > $increment)
			{
				$count = (int)floor($seconds / $increment);
				$interval->$property = $count;
				$seconds = $seconds - ($count * $increment);
			}
		}

		$interval->s = (int)$seconds;

		return $interval;
	}

	/**
	 * Returns the total number of seconds in the interval.
	 *
	 * @param \DateInterval $interval The date interval.
	 *
	 * @return string The number of seconds.
	 */
	public function toSeconds(\DateInterval $interval = null)
	{
		if (($interval === null) && isset($this))
		{
			$interval = $this;
		}

		$seconds = (int)$interval->s;

		if ($interval->i)
		{
			$seconds = $seconds + ((int)$interval->i * self::SECONDS_MINUTE);
		}

		if ($interval->h)
		{
			$seconds = $seconds + ((int)$interval->h * self::SECONDS_HOUR);
		}

		if ($interval->d)
		{
			$seconds = $seconds + ((int)$interval->d * self::SECONDS_DAY);
		}

		if ($interval->m)
		{
			$seconds = $seconds + ((int)$interval->m * self::SECONDS_MONTH);
		}

		if ($interval->y)
		{
			$seconds = $seconds + ((int)$interval->y * self::SECONDS_YEAR);
		}

		return (string)$seconds;
	}

	/**
	 * Returns the interval specification.
	 *
	 * @param \DateInterval $interval The date interval.
	 *
	 * @return string The interval specification.
	 */
	public function toSpec(\DateInterval $interval = null)
	{
		if (($interval === null) && isset($this))
		{
			$interval = $this;
		}

		$string = 'P';

		foreach (self::$_date as $property => $suffix)
		{
			if ($interval->{$property})
			{
				$string .= $interval->{$property}.$suffix;
			}
		}

		if ($interval->h || $interval->i || $interval->s)
		{
			$string .= 'T';

			foreach (self::$_time as $property => $suffix)
			{
				if ($interval->{$property})
				{
					$string .= $interval->{$property}.$suffix;
				}
			}
		}

		return $string;
	}

	/**
	 * Returns the interval in a human-friendly string.
	 *
	 * @param bool $showSeconds
	 *
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
