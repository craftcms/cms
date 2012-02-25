<?php
namespace Blocks;

/**
 *
 */
class ConfigHelperTag extends Tag
{
	/**
	 * @param $time
	 * @return int
	 */
	public function getTimeInSeconds($time)
	{
		return ConfigHelper::getTimeInSeconds($time);
	}
}
