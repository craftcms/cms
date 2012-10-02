<?php
namespace Blocks;

/**
 *
 */
class DateTime extends \DateTime
{
	const W3C_DATE = 'Y-m-d';

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
	 * @return DateInterval|false
	 */
	public function diff($datetime2, $absolute = false)
	{
		$interval = parent::diff($datetime2, $absolute);

		// Convert it to a DateInterval in this namespace
		if ($interval instanceof \DateInterval)
		{
			$newInterval = new DateInterval();
			$newInterval->y = $interval->y;
			$newInterval->m = $interval->m;
			$newInterval->d = $interval->d;
			$newInterval->h = $interval->h;
			$newInterval->i = $interval->i;
			$newInterval->s = $interval->s;
			$newInterval->invert = $interval->invert;
			$newInterval->days = $interval->days;
			return $newInterval;
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

	/**
	 * @return string
	 */
	function __toString()
	{
		return $this->format('M j, Y');
	}
}
