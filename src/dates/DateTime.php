<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\dates;

use Craft;
use craft\app\helpers\DateTimeHelper;
use craft\app\i18n\Locale;

/**
 * Class DateTime
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DateTime extends \DateTime
{
	// Constants
	// =========================================================================

	const W3C_DATE = 'Y-m-d';
	const MYSQL_DATETIME = 'Y-m-d H:i:s';
	const UTC = 'UTC';
	const DATEFIELD_24HOUR = 'Y-m-d H:i';
	const DATEFIELD_12HOUR = 'Y-m-d h:i A';

	// Public Methods
	// =========================================================================

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->format(static::W3C_DATE);
	}

	/**
	 * Creates a new [[DateTime]] object (rather than \DateTime)
	 *
	 * @param string $format
	 * @param string $time
	 * @param mixed  $timezone The timezone the string is set in (defaults to UTC).
	 *
	 * @return DateTime
	 */
	public static function createFromFormat($format, $time, $timezone = null)
	{
		if (!$timezone)
		{
			// Default to UTC
			$timezone = static::UTC;
		}

		if (is_string($timezone))
		{
			$timezone = new \DateTimeZone($timezone);
		}

		$dateTime = parent::createFromFormat($format, $time, $timezone);

		if ($dateTime)
		{
			$timeStamp = $dateTime->getTimestamp();

			if (DateTimeHelper::isValidTimeStamp($timeStamp))
			{
				return new DateTime('@'.$dateTime->getTimestamp());
			}
		}

		return false;
	}

	/**
	 * @param string $format
	 * @param mixed  $timezone The timezone to output the date in (defaults to the current app timezone).
	 *
	 * @return string
	 */
	public function format($format, $timezone = null)
	{
		if (!$timezone)
		{
			// Default to the current app timezone
			$timezone = Craft::$app->timezone;
		}

		if (is_string($timezone))
		{
			$timezone = new \DateTimeZone($timezone);
		}

		$this->setTimezone($timezone);
		return parent::format($format);
	}

	/**
	 * @return string
	 */
	public function atom()
	{
		return $this->format(static::ATOM, static::UTC);
	}

	/**
	 * @return string
	 */
	public function cookie()
	{
		return $this->format(static::COOKIE, static::UTC);
	}

	/**
	 * @return string
	 */
	public function iso8601()
	{
		return $this->format(static::ISO8601, static::UTC);
	}

	/**
	 * @return string
	 */
	public function rfc822()
	{
		return $this->format(static::RFC822, static::UTC);
	}

	/**
	 * @return string
	 */
	public function rfc850()
	{
		return $this->format(static::RFC850, static::UTC);
	}

	/**
	 * @return string
	 */
	public function rfc1036()
	{
		return $this->format(static::RFC1036, static::UTC);
	}

	/**
	 * @return string
	 */
	public function rfc1123()
	{
		return $this->format(static::RFC1123, static::UTC);
	}

	/**
	 * @return string
	 */
	public function rfc2822()
	{
		return $this->format(static::RFC2822, static::UTC);
	}

	/**
	 * @return string
	 */
	public function rfc3339()
	{
		return $this->format(static::RFC3339, static::UTC);
	}

	/**
	 * @return string
	 */
	public function rss()
	{
		return $this->format(static::RSS, static::UTC);
	}

	/**
	 * @return string
	 */
	public function w3c()
	{
		return $this->format(static::W3C);
	}

	/**
	 * @return string
	 */
	public function w3cDate()
	{
		return $this->format(static::W3C_DATE);
	}

	/**
	 * @return string
	 */
	public function mySqlDateTime()
	{
		return $this->format(static::MYSQL_DATETIME);
	}

	/**
	 * @return string
	 */
	public function localeDate()
	{
		return Craft::$app->getLocale()->getFormatter()->asDate($this, 'short');
	}

	/**
	 * @return string
	 */
	public function localeTime()
	{
		return Craft::$app->getLocale()->getFormatter()->asTime($this, 'short');
	}

	/**
	 * @return string
	 */
	public function year()
	{
		return $this->format('Y');
	}

	/**
	 * @return string
	 */
	public function month()
	{
		return $this->format('n');
	}

	/**
	 * @return string
	 */
	public function day()
	{
		return $this->format('j');
	}

	/**
	 * @param \DateTime $datetime2
	 * @param bool      $absolute
	 *
	 * @return DateInterval
	 */
	public function diff($datetime2, $absolute = false)
	{
		$interval = parent::diff($datetime2, $absolute);

		// Convert it to a DateInterval in this namespace
		if ($interval instanceof \DateInterval)
		{
			$spec = 'P';

			if ($interval->y) $spec .= $interval->y.'Y';
			if ($interval->m) $spec .= $interval->m.'M';
			if ($interval->d) $spec .= $interval->d.'D';

			if ($interval->h || $interval->i || $interval->s)
			{
				$spec .= 'T';

				if ($interval->h) $spec .= $interval->h.'H';
				if ($interval->i) $spec .= $interval->i.'M';
				if ($interval->s) $spec .= $interval->s.'S';
			}

			// If $spec is P at this point, the interval was less than a second. Accuracy be damned.
			if ($spec === 'P')
			{
				$spec = 'PT0S';
			}

			$newInterval = new DateInterval($spec);
			$newInterval->invert = $interval->invert;

			// Apparently 'days' is a read-only property. Oh well.
			//$newInterval->days = $interval->days;

			return $newInterval;
		}
		else
		{
			return $interval;
		}
	}

	/**
	 * Returns a nicely formatted date string.
	 *
	 * @return string
	 */
	public function nice()
	{
		return DateTimeHelper::nice($this->getTimestamp());
	}

	/**
	 * Returns a UI-facing timestamp.
	 *
	 * - If the date/time is from today, only the time will be retuned in a localized format (e.g. “10:00 AM”).
	 * - If the date/time is from yesterday, “Yesterday” will be returned.
	 * - If the date/time is from the last 7 days, the name of the day will be returned (e.g. “Monday”).
	 * - Otherwise, the date will be returned in a localized format (e.g. “12/2/2014”).
	 *
	 * @return string
	 */
	public function uiTimestamp()
	{
		return DateTimeHelper::uiTimestamp($this);
	}
}
