<?php
namespace Blocks;

/**
 * Plugin functions
 */
class PluginsVariable
{
	/**
	 * Returns a plugin.
	 * @param string $class
	 * @return Plugin
	 */
	public function get($class)
	{
		return b()->plugins->getPlugin($class);
	}

	/**
	 * Returns all plugins.
	 * @return array
	 */
	public function all()
	{
		return b()->plugins->getAll(true, true);
	}
}
