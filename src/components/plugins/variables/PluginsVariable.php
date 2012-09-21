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
	 * @param bool   $enabledOnly
	 * @return PluginRecord
	 */
	public function get($class, $enabledOnly = true)
	{
		return blx()->plugins->getPlugin($class, $enabledOnly);
	}

	/**
	 * Returns all plugins.
	 *
	 * @return array
	 */
	public function all()
	{
		return blx()->plugins->getPlugins();
	}
}
