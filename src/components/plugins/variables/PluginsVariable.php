<?php
namespace Blocks;

/**
 * Plugin functions
 */
class PluginsVariable
{
	/**
	 * Returns a plugin.
	 *
	 * @param string $class
	 * @return Plugin
	 */
	public function get($class)
	{
		return blx()->plugins->getPlugin($class);
	}

	/**
	 * Returns all plugins.
	 *
	 * @return array
	 */
	public function all()
	{
		return blx()->plugins->getAllPlugins(true, true);
	}
}
