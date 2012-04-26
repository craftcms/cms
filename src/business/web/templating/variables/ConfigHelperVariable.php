<?php
namespace Blocks;

/**
 *
 */
class ConfigHelperVariable
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
