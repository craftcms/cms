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
	public function getPlugin($class, $enabledOnly = true)
	{
		$plugin = blx()->plugins->getPlugin($class, $enabledOnly);
		if ($plugin)
		{
			return new PluginVariable($plugin);
		}
	}

	/**
	 * Returns all plugins.
	 *
	 * @return array
	 */
	public function plugins()
	{
		$plugins = blx()->plugins->getPlugins();
		return VariableHelper::populateVariables($plugins, 'PluginVariable');
	}
}
