<?php
namespace Blocks;

/**
 * Config functions
 */
class ConfigVariable
{
	/**
	 * Returns whether a config item exists.
	 *
	 * @param string $name
	 * @return bool
	 */
	function __isset($name)
	{
		return isset(blx()->params['blocksConfig'][$name]);
	}

	/**
	 * Returns a config item.
	 *
	 * @param string $name
	 * @return string
	 */
	function __get($name)
	{
		return (string)blx()->config->getItem($name);
	}
}
