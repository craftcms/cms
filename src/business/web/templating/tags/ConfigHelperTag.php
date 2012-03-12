<?php
namespace Blocks;

/**
 *
 */
class ConfigHelperTag
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
