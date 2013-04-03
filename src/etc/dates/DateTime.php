<?php
namespace Craft;

/**
 *
 */
class DateTime extends \DateTime
{
	const W3C_DATE = 'Y-m-d';
	const MYSQL_DATETIME = 'Y-m-d H:i:s';

	/**
	 * Creates a new \Craft\DateTime object (rather than \DateTime)
	 *
	 * @param string $format
	 * @param string $time
	 * @param \DateTimeZone|null $timezone
	 * @return DateTime
	 */
	public static function createFromFormat($format, $time, $timezone = null)
	{
		if ($timezone === null)
		{
			$timezone = new \DateTimeZone('UTC');
		}

		$dateTime = parent::createFromFormat($format, $time, $timezone);

		$timeStamp = $dateTime->getTimestamp();
		if (DateTimeHelper::isValidTimeStamp($timeStamp))
		{
			return new DateTime('@'.$dateTime->getTimestamp());
		}
	}

	/**
	 * Creates a new DateTime object from a string.
	 *
	 * Supports the following formats:
	 *
	 *  - All W3C date and time formats (http://www.w3.org/TR/NOTE-datetime)
	 *  - MySQL DATE and DATETIME formats (http://dev.mysql.com/doc/refman/5.1/en/datetime.html)
	 *  - Relaxed versions of W3C and MySQL formats (single-digit months, days, and hours)
	 *  - Unix timestamps
	 *
	 * @param string      $date
	 * @param stirng|null $timezone The PHP timezone identifier, if not specified in $date. Defaults to UTC. (See http://php.net/manual/en/timezones.php)
	 * @return DateTime
	 */
	public static function createFromString($date, $timezone = null)
	{
		$date = (string) $date;

		if (preg_match('/^
			(?P<year>\d{4})                                 # YYYY (four digit year)
			(?:
				-(?P<mon>\d\d?)                             # -M or -MM (one or two digit month)
				(?:
					-(?P<day>\d\d?)                         # -D or -DD (one or two digit day)
					(?:
						[T\ ](?P<hour>\d\d?)\:(?P<min>\d\d) # [T or space]hh:mm (one or two digit hour and two digit minute)
						(?:
							\:(?P<sec>\d\d)                 # :ss (two digit second)
							(?:\.\d+)?                      # .s (decimal fraction of a second -- not supported)
						)?
						(?:Z|(?P<tzd>[+\-]\d\d\:\d\d))?     # Z or [+ or -]hh:ss (UTC or a timezone offset)
					)?
				)?
			)?$/x', $date, $m))
		{
			$format = 'Y-m-d H:i:s';

			$date = $m['year'] .
				'-'.(!empty($m['mon'])  ? sprintf('%02d', $m['mon'])  : '01') .
				'-'.(!empty($m['day'])  ? sprintf('%02d', $m['day'])  : '01') .
				' '.(!empty($m['hour']) ? sprintf('%02d', $m['hour']) : '00') .
				':'.(!empty($m['min'])  ? $m['min']                   : '00') .
				':'.(!empty($m['sec'])  ? $m['sec']                   : '00');

			if (!empty($m['tzd']))
			{
				$format .= 'P';
				$date   .= $m['tzd'];
			}
			else if ($timezone !== null)
			{
				$format .= 'e';
				$date   .= $timezone;
			}
		}
		else if (preg_match('/^\d{10}$/', $date))
		{
			$format = 'U';
		}
		else
		{
			$format = '';
		}

		return static::createFromFormat('!'.$format, $date);
	}

	/**
	 * @return string
	 */
	function __toString()
	{
		return $this->format('M j, Y');
	}

	/**
	 * @param string $format
	 * @param bool   $setTimezone Whether to output the string in the current timezone.
	 * @return string
	 */
	function format($format, $setTimezone = true)
	{
		if ($setTimezone)
		{
			$this->setTimezone(new \DateTimeZone(craft()->timezone));
		}

		return parent::format($format);
	}

	/**
	 * @return string
	 */
	public function atom()
	{
		return $this->format(static::ATOM);
	}

	/**
	 * @return string
	 */
	public function cookie()
	{
		return $this->format(static::COOKIE);
	}

	/**
	 * @return string
	 */
	public function iso8601()
	{
		return $this->format(static::ISO8601);
	}

	/**
	 * @return string
	 */
	public function rfc822()
	{
		return $this->format(static::RFC822);
	}

	/**
	 * @return string
	 */
	public function rfc850()
	{
		return $this->format(static::RFC850);
	}

	/**
	 * @return string
	 */
	public function rfc1036()
	{
		return $this->format(static::RFC1036);
	}

	/**
	 * @return string
	 */
	public function rfc1123()
	{
		return $this->format(static::RFC1123);
	}

	/**
	 * @return string
	 */
	public function rfc2822()
	{
		return $this->format(static::RFC2822);
	}

	/**
	 * @return string
	 */
	public function rfc3339()
	{
		return $this->format(static::RFC3339);
	}

	/**
	 * @return string
	 */
	public function rss()
	{
		return $this->format(static::RSS);
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

			return new DateInterval($spec);
		}
		else
		{
			return $interval;
		}
	}

	/**
	 * Returns a nicely formatted date string for given Datetime string.
	 *
	 * @return string
	 */
	public function nice()
	{
		return DateTimeHelper::nice($this->getTimestamp());
	}
}
