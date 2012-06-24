<?php
namespace Blocks;

/**
 * Config functions
 */
class ConfigVariable
{
	/**
	 * Returns a config item.
	 * @param string $name
	 * @return string
	 */
	function __get($name)
	{
		return (string)blx()->config->getItem($name);
	}
}
