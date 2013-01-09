<?php
namespace Blocks;

/**
 *
 */
class DateTime extends \DateTime
{
	const W3C_DATE = 'Y-m-d';
	const MYSQL_DATETIME = 'Y-m-d H:i:s';

	/**
	 * Creates a new \Blocks\DateTime object (rather than \DateTime)
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
			$dateTime = parent::createFromFormat($format, $time);
		}
		else
		{
			$dateTime = parent::createFromFormat($format, $time, $timezone);
		}

		return new DateTime('@'.$dateTime->getTimestamp());
	}

	/**
	 * @return string
	 */
	function __toString()
	{
		return $this->format('M j, Y');
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
