<?php
namespace Blocks;

/**
 * Config functions
 */
class ConfigVariable
{
	/**
	 * Returns a config item
	 */
	function __get($name)
	{
		return b()->config->getItem($name);
	}

	/**
	 * @param $time
	 * @return int
	 */
	public function getTimeInSeconds($time)
	{
		return ConfigHelper::getTimeInSeconds($time);
	}
}
