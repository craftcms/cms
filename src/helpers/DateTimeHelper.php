<?php
namespace Craft;

/**
 * Class DateTimeHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class DateTimeHelper
{
	// Public Methods
	// =========================================================================

	/**
	 * @return DateTime
	 */
	public static function currentUTCDateTime()
	{
		return new DateTime(null, new \DateTimeZone('UTC'));
	}

	/**
	 * @return int
	 */
	public static function currentTimeStamp()
	{
		$date = static::currentUTCDateTime();
		return $date->getTimestamp();
	}

	/**
	 * @return string
	 */
	public static function currentTimeForDb()
	{
		// Eventually this will return the time in the appropriate database format for MySQL, Postgre, etc. For now,
		// it's MySQL only.
		$date = static::currentUTCDateTime();
		return $date->format(DateTime::MYSQL_DATETIME, DateTime::UTC);
	}

	/**
	 * @param $timeStamp
	 *
	 * @return DateTime
	 */
	public static function formatTimeForDb($timeStamp = null)
	{
		// Eventually this will accept a database parameter and format the timestamp for the given database date/time
		// datatype. For now, it's MySQL only.

		if ($timeStamp)
		{
			if ($timeStamp instanceof \DateTime)
			{
				$dt = $timeStamp;
			}
			else if (static::isValidTimeStamp($timeStamp))
			{
				$dt = new DateTime('@'.$timeStamp);
			}
			else
			{
				$dt = new DateTime($timeStamp);
			}
		}
		else
		{
			$dt = new DateTime();
		}

		return $dt->format(DateTime::MYSQL_DATETIME, new \DateTimeZone(DateTime::UTC));
	}

	/**
	 * @param int  $seconds     The number of seconds
	 * @param bool $showSeconds Whether to output seconds or not
	 *
	 * @return string
	 */
	public static function secondsToHumanTimeDuration($seconds, $showSeconds = true)
	{
		$secondsInWeek   = 604800;
		$secondsInDay    = 86400;
		$secondsInHour   = 3600;
		$secondsInMinute = 60;

		$weeks = floor($seconds / $secondsInWeek);
		$seconds = $seconds % $secondsInWeek;

		$days = floor($seconds / $secondsInDay);
		$seconds = $seconds % $secondsInDay;

		$hours = floor($seconds / $secondsInHour);
		$seconds = $seconds % $secondsInHour;

		if ($showSeconds)
		{
			$minutes = floor($seconds / $secondsInMinute);
			$seconds = $seconds % $secondsInMinute;
		}
		else
		{
			$minutes = round($seconds / $secondsInMinute);
			$seconds = 0;
		}

		$timeComponents = array();

		if ($weeks)
		{
			$timeComponents[] = $weeks.' '.($weeks == 1 ? Craft::t('week') : Craft::t('weeks'));
		}

		if ($days)
		{
			$timeComponents[] = $days.' '.($days == 1 ? Craft::t('day') : Craft::t('days'));
		}

		if ($hours)
		{
			$timeComponents[] = $hours.' '.($hours == 1 ? Craft::t('hour') : Craft::t('hours'));
		}

		if ($minutes || (!$showSeconds && !$weeks && !$days && !$hours))
		{
			$timeComponents[] = $minutes.' '.($minutes == 1 ? Craft::t('minute') : Craft::t('minutes'));
		}

		if ($seconds || ($showSeconds && !$weeks && !$days && !$hours && !$minutes))
		{
			$timeComponents[] = $seconds.' '.($seconds == 1 ? Craft::t('second') : Craft::t('seconds'));
		}

		return implode(', ', $timeComponents);
	}

	/**
	 * @param $timestamp
	 *
	 * @return bool
	 */
	public static function isValidTimeStamp($timestamp)
	{
		return (is_numeric($timestamp) && ($timestamp <= PHP_INT_MAX) && ($timestamp >= ~PHP_INT_MAX));
	}

	/**
	 * Returns a nicely formatted date string for given Datetime string.
	 *
	 * @param string $dateString The date string.
	 *
	 * @return string Formatted date string
	 */
	public static function nice($dateString = null)
	{
		if ($dateString == null)
		{
			$date = time();
		}
		else
		{
			if (static::isValidTimeStamp($dateString))
			{
				$date = $dateString;
			}
			else
			{
				$date = strtotime($dateString);
			}
		}

		return craft()->dateFormatter->formatDateTime($date);
	}

	/**
	 * Returns a formatted descriptive date string for given datetime string.
	 *
	 * If the given date is today, the returned string could be "Today, 6:54 pm". If the given date was yesterday, the
	 * returned string could be "Yesterday, 6:54 pm". If $dateString's year is the current year, the returned string
	 * does not include mention of the year.
	 *
	 * @param string $dateString Datetime string or Unix timestamp
	 *
	 * @return string Described, relative date string
	 */
	public static function niceShort($dateString = null)
	{
		$date = ($dateString == null) ? time() : strtotime($dateString);

		$y = (static::isThisYear($date)) ? '' : ' Y';

		if (static::isToday($date))
		{
			$ret = sprintf('Today, %s', date("g:i a", $date));
		}
		elseif (static::wasYesterday($date))
		{
			$ret = sprintf('Yesterday, %s', date("g:i a", $date));
		}
		else
		{
			$ret = date("M jS{$y}, H:i", $date);
		}

		return $ret;
	}

	/**
	 * Returns true if given date is today.
	 *
	 * @param string $date Unix timestamp
	 *
	 * @return bool true if date is today, false otherwise.
	 */
	public static function isToday($date)
	{
		$date = new DateTime('@'.$date);
		$now = new DateTime();

		return $date->format('Y-m-d') == $now->format('Y-m-d');
	}

	/**
	 * Returns true if given date was yesterday
	 *
	 * @param string $date Unix timestamp
	 *
	 * @return bool true if date was yesterday, false otherwise.
	 */
	public static function wasYesterday($date)
	{
		$date = new DateTime('@'.$date);
		$yesterday = new DateTime('@'.strtotime('yesterday'));

		return $date->format('Y-m-d') == $yesterday->format('Y-m-d');
	}

	/**
	 * Returns true if given date is in this year
	 *
	 * @param string $date Unix timestamp
	 *
	 * @return bool true if date is in this year, false otherwise.
	 */
	public static function isThisYear($date)
	{
		$date = new DateTime('@'.$date);
		$now = new DateTime();

		return $date->format('Y') == $now->format('Y');
	}

	/**
	 * Returns true if given date is in this week
	 *
	 * @param string $date Unix timestamp
	 *
	 * @return bool true if date is in this week, false otherwise.
	 */
	public static function isThisWeek($date)
	{
		$date = new DateTime('@'.$date);
		$now = new DateTime();

		return $date->format('W Y') == $now->format('W Y');
	}

	/**
	 * Returns true if given date is in this month
	 *
	 * @param string $date Unix timestamp
	 *
	 * @return bool True if date is in this month, false otherwise.
	 */
	public static function isThisMonth($date)
	{
		$date = new DateTime('@'.$date);
		$now = new DateTime();

		return $date->format('m Y') == $now->format('m Y');
	}

	/**
	 * Returns true if specified datetime was within the interval specified, else false.
	 *
	 * @param mixed $timeInterval The numeric value with space then time type. Example of valid types: 6 hours, 2 days,
	 *                            1 minute.
	 * @param mixed $dateString   The datestring or unix timestamp to compare
	 * @param int   $userOffset   User's offset from GMT (in hours)
	 *
	 * @return bool Whether the $dateString was within the specified $timeInterval.
	 */
	public static function wasWithinLast($timeInterval, $dateString, $userOffset = null)
	{
		if (is_numeric($timeInterval))
		{
			$timeInterval = $timeInterval.' days';
		}

		$date = static::fromString($dateString, $userOffset);
		$interval = static::fromString('-'.$timeInterval);

		if ($date >= $interval && $date <= time())
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns true if the specified date was in the past, otherwise false.
	 *
	 * @param mixed $date The datestring (a valid strtotime) or unix timestamp to check.
	 *
	 * @return bool true if the specified date was in the past, false otherwise.
	 */
	public static function wasInThePast($date)
	{
		return static::fromString($date) < time() ? true : false;
	}

	/**
	 * Returns a UI-facing timestamp for a given {@link DateTime} object.
	 *
	 * - If the date/time is from today, only the time will be retuned in a localized format (e.g. “10:00 AM”).
	 * - If the date/time is from yesterday, “Yesterday” will be returned.
	 * - If the date/time is from the last 7 days, the name of the day will be returned (e.g. “Monday”).
	 * - Otherwise, the date will be returned in a localized format (e.g. “12/2/2014”).
	 *
	 * @param DateTime $dateTime The DateTime object to be formatted.
	 *
	 * @return string The timestamp.
	 */
	public static function uiTimestamp(DateTime $dateTime)
	{
		// If it's today, just return the local time.
		if (static::isToday($dateTime->getTimestamp()))
		{
			return $dateTime->localeTime();
		}
		// If it was yesterday, display 'Yesterday'
		else if (static::wasYesterday($dateTime->getTimestamp()))
		{
			return Craft::t('Yesterday');
		}
		// If it were up to 7 days ago, display the weekday name.
		else if (static::wasWithinLast('7 days', $dateTime->getTimestamp()))
		{
			return Craft::t($dateTime->format('l'));
		}
		else
		{
			// Otherwise, just return the local date.
			return $dateTime->localeDate();
		}
	}

	/**
	 * Returns either a relative date or a formatted date depending on the difference between the current time and given
	 * datetime. $datetime should be in a **strtotime**-parsable format, like MySQL's
	 * datetime datatype.
	 *
	 * Options:
	 *  * 'format' => a fall back format if the relative time is longer than the duration specified by end
	 *  * 'end' =>  The end of relative time telling
	 *
	 * Relative dates look something like this:
	 *  3 weeks, 4 days ago
	 *  15 seconds ago
	 * Formatted dates look like this:
	 *  on 02/18/2004
	 *
	 * The returned string includes 'ago' or 'on' and assumes you'll properly add a word  like 'Posted ' before the
	 * function output.
	 *
	 * @param       $dateTime
	 * @param array $options Default format if timestamp is used in $dateString
	 *
	 * @return string The relative time string.
	 */
	public static function timeAgoInWords($dateTime, $options = array())
	{
		$now = time();

		$inSeconds = strtotime($dateTime);
		$backwards = ($inSeconds > $now);

		$format = 'j/n/y';
		$end = '+1 month';

		if (is_array($options))
		{
			if (isset($options['format']))
			{
				$format = $options['format'];
				unset($options['format']);
			}
			if (isset($options['end']))
			{
				$end = $options['end'];
				unset($options['end']);
			}
		}
		else
		{
			$format = $options;
		}

		if ($backwards)
		{
			$futureTime = $inSeconds;
			$pastTime = $now;
		}
		else
		{
			$futureTime = $now;
			$pastTime = $inSeconds;
		}

		$diff = $futureTime - $pastTime;

		// If more than a week, then take into account the length of months
		if ($diff >= 604800)
		{
			list($future['H'], $future['i'], $future['s'], $future['d'], $future['m'], $future['Y']) = explode('/', date('H/i/s/d/m/Y', $futureTime));
			list($past['H'], $past['i'], $past['s'], $past['d'], $past['m'], $past['Y']) = explode('/', date('H/i/s/d/m/Y', $pastTime));

			$years = $months = $weeks = $days = $hours = $minutes = $seconds = 0;

			if ($future['Y'] == $past['Y'] && $future['m'] == $past['m'])
			{
				$months = 0;
				$years = 0;
			}
			else
			{
				if ($future['Y'] == $past['Y'])
				{
					$months = $future['m'] - $past['m'];
				}
				else
				{
					$years = $future['Y'] - $past['Y'];
					$months = $future['m'] + ((12 * $years) - $past['m']);

					if ($months >= 12)
					{
						$years = floor($months / 12);
						$months = $months - ($years * 12);
					}

					if ($future['m'] < $past['m'] && $future['Y'] - $past['Y'] == 1)
					{
						$years--;
					}
				}
			}

			if ($future['d'] >= $past['d'])
			{
				$days = $future['d'] - $past['d'];
			}
			else
			{
				$daysInPastMonth = date('t', $pastTime);
				$daysInFutureMonth = date('t', mktime(0, 0, 0, $future['m'] - 1, 1, $future['Y']));

				if (!$backwards)
				{
					$days = ($daysInPastMonth - $past['d']) + $future['d'];
				}
				else
				{
					$days = ($daysInFutureMonth - $past['d']) + $future['d'];
				}

				if ($future['m'] != $past['m'])
				{
					$months--;
				}
			}

			if ($months == 0 && $years >= 1 && $diff < ($years * 31536000))
			{
				$months = 11;
				$years--;
			}

			if ($months >= 12)
			{
				$years = $years + 1;
				$months = $months - 12;
			}

			if ($days >= 7)
			{
				$weeks = floor($days / 7);
				$days = $days - ($weeks * 7);
			}
		}
		else
		{
			$years = $months = $weeks = 0;
			$days = floor($diff / 86400);

			$diff = $diff - ($days * 86400);

			$hours = floor($diff / 3600);
			$diff = $diff - ($hours * 3600);

			$minutes = floor($diff / 60);
			$diff = $diff - ($minutes * 60);
			$seconds = $diff;
		}

		$relativeDate = '';
		$diff = $futureTime - $pastTime;

		if ($diff > abs($now - strtotime($end)))
		{
			$relativeDate = sprintf('on %s', date($format, $inSeconds));
		}
		else
		{
			if ($years > 0)
			{
				// years and months and days
				$relativeDate .= ($relativeDate ? ', ' : '').$years.' '.($years == 1 ? 'year' : 'years');
				$relativeDate .= $months > 0 ? ($relativeDate ? ', ' : '').$months.' '.($months == 1 ? 'month' : 'months') : '';
				$relativeDate .= $weeks > 0 ? ($relativeDate ? ', ' : '').$weeks.' '.($weeks == 1 ? 'week' : 'weeks') : '';
				$relativeDate .= $days > 0 ? ($relativeDate ? ', ' : '').$days.' '.($days == 1 ? 'day' : 'days') : '';
			}
			elseif (abs($months) > 0)
			{
				// months, weeks and days
				$relativeDate .= ($relativeDate ? ', ' : '').$months.' '.($months == 1 ? 'month' : 'months');
				$relativeDate .= $weeks > 0 ? ($relativeDate ? ', ' : '').$weeks.' '.($weeks == 1 ? 'week' : 'weeks') : '';
				$relativeDate .= $days > 0 ? ($relativeDate ? ', ' : '').$days.' '.($days == 1 ? 'day' : 'days') : '';
			}
			elseif (abs($weeks) > 0)
			{
				// weeks and days
				$relativeDate .= ($relativeDate ? ', ' : '').$weeks.' '.($weeks == 1 ? 'week' : 'weeks');
				$relativeDate .= $days > 0 ? ($relativeDate ? ', ' : '').$days.' '.($days == 1 ? 'day' : 'days') : '';
			}
			elseif (abs($days) > 0)
			{
				// days and hours
				$relativeDate .= ($relativeDate ? ', ' : '').$days.' '.($days == 1 ? 'day' : 'days');
				$relativeDate .= $hours > 0 ? ($relativeDate ? ', ' : '').$hours.' '.($hours == 1 ? 'hour' : 'hours') : '';
			}
			elseif (abs($hours) > 0)
			{
				// hours and minutes
				$relativeDate .= ($relativeDate ? ', ' : '').$hours.' '.($hours == 1 ? 'hour' : 'hours');
				$relativeDate .= $minutes > 0 ? ($relativeDate ? ', ' : '').$minutes.' '.($minutes == 1 ? 'minute' : 'minutes') : '';
			}
			elseif (abs($minutes) > 0)
			{
				// minutes only
				$relativeDate .= ($relativeDate ? ', ' : '').$minutes.' '.($minutes == 1 ? 'minute' : 'minutes');
			}
			else
			{
				// seconds only
				$relativeDate .= ($relativeDate ? ', ' : '').$seconds.' '.($seconds == 1 ? 'second' : 'seconds');
			}

			if (!$backwards)
			{
				$relativeDate = sprintf('%s ago', $relativeDate);
			}
		}

		return $relativeDate;
	}

	/**
	 * Returns a UNIX timestamp, given either a UNIX timestamp or a valid strtotime() date string.
	 *
	 * @param string $dateString Datetime string
	 * @param int    $userOffset User's offset from GMT (in hours)
	 *
	 * @return string The parsed timestamp.
	 */
	public static function fromString($dateString, $userOffset = null)
	{
		if (empty($dateString))
		{
			return false;
		}

		if (is_integer($dateString) || is_numeric($dateString))
		{
			$date = intval($dateString);
		}
		else
		{
			$date = strtotime($dateString);
		}

		if ($userOffset !== null)
		{
			//return $this->convert($date, $userOffset);
		}

		if ($date === -1)
		{
			return false;
		}

		return $date;
	}

	/**
	 * Takes a PHP time format string and converts it to seconds.
	 * {@see http://www.php.net/manual/en/datetime.formats.time.php}
	 *
	 * @param $timeFormatString
	 *
	 * @return string
	 */
	public static function timeFormatToSeconds($timeFormatString)
	{
		$interval = new DateInterval($timeFormatString);
		return $interval->toSeconds();
	}

	/**
	 * Returns true if interval string is a valid interval.
	 *
	 * @param $intervalString
	 *
	 * @return bool
	 */
	public static function isValidIntervalString($intervalString)
	{
		$interval = DateInterval::createFromDateString($intervalString);

		if ($interval->s != 0 || $interval->i != 0 || $interval->h != 0 || $interval->d != 0 || $interval->m != 0 || $interval->y != 0)
		{
			return true;
		}

		return false;
	}
}
