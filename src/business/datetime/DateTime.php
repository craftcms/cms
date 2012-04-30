<?php
namespace Blocks;

/**
 *
 */
class DateTime extends \DateTime
{
	/**
	 * @return string
	 */
	public function atom()
	{
		return $this->format(self::ATOM);
	}

	/**
	 * @return string
	 */
	public function cookie()
	{
		return $this->format(self::COOKIE);
	}

	/**
	 * @return string
	 */
	public function iso8601()
	{
		return $this->format(self::ISO8601);
	}

	/**
	 * @return string
	 */
	public function rfc822()
	{
		return $this->format(self::RFC822);
	}

	/**
	 * @return string
	 */
	public function rfc850()
	{
		return $this->format(self::RFC850);
	}

	/**
	 * @return string
	 */
	public function rfc1036()
	{
		return $this->format(self::RFC1036);
	}

	/**
	 * @return string
	 */
	public function rfc1123()
	{
		return $this->format(self::RFC1123);
	}

	/**
	 * @return string
	 */
	public function rfc2822()
	{
		return $this->format(self::RFC2822);
	}

	/**
	 * @return string
	 */
	public function rfc3339()
	{
		return $this->format(self::RFC3339);
	}

	/**
	 * @return string
	 */
	public function rss()
	{
		return $this->format(self::RSS);
	}

	/**
	 * @return string
	 */
	public function w3c()
	{
		return $this->format(self::W3C);
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
	 * @return string
	 */
	function __toString()
	{
		return $this->format('M j, Y');
	}
}
