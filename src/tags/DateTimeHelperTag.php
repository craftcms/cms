<?php
namespace Blocks;

/**
 *
 */
class DateTimeHelperTag extends Tag
{
	/**
	 * @param $seconds
	 * @return int
	 */
	public function niceSeconds($seconds)
	{
		return DateTimeHelper::niceSeconds($seconds);
	}
}
