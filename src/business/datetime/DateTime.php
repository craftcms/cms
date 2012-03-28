<?php
namespace Blocks;

/**
 *
 */
class DateTime extends \DateTime
{
	public function atom()
	{
		return $this->format(self::ATOM);
	}

	public function cookie()
	{
		return $this->format(self::COOKIE);
	}

	public function iso8601()
	{
		return $this->format(self::ISO8601);
	}

	public function rfc822()
	{
		return $this->format(self::RFC822);
	}

	public function rfc850()
	{
		return $this->format(self::RFC850);
	}

	public function rfc1036()
	{
		return $this->format(self::RFC1036);
	}

	public function rfc1123()
	{
		return $this->format(self::RFC1123);
	}

	public function rfc2822()
	{
		return $this->format(self::RFC2822);
	}

	public function rfc3339()
	{
		return $this->format(self::RFC3339);
	}

	public function rss()
	{
		return $this->format(self::RSS);
	}

	public function w3c()
	{
		return $this->format(self::W3C);
	}

	public function __toString()
	{
		return $this->format('M j, Y');
	}
}
