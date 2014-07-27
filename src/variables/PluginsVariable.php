<?php
namespace Craft;

/**
 * Plugin functions.
 *
 * @package craft.app.validators
 */
class PluginsVariable
{
	/**
	 * Returns a plugin by its class handle.
	 *
	 * @param string $class
	 * @param bool   $enabledOnly
	 * @return PluginVariable|null
	 */
	public function getPlugin($class, $enabledOnly = true)
	{
		$plugin = craft()->plugins->getPlugin($class, $enabledOnly);

		if ($plugin)
		{
			return new PluginVariable($plugin);
		}
	}

	/**
	 * Returns all plugins.
	 *
	 * @param bool $enabledOnly
	 * @return array
	 */
	public function getPlugins($enabledOnly = true)
	{
		$plugins = craft()->plugins->getPlugins($enabledOnly);
		return PluginVariable::populateVariables($plugins);
	}
}
