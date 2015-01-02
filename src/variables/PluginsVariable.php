<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

/**
 * Plugin functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PluginsVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns a plugin by its class handle.
	 *
	 * @param string $class
	 * @param bool   $enabledOnly
	 *
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
	 *
	 * @return array
	 */
	public function getPlugins($enabledOnly = true)
	{
		$plugins = craft()->plugins->getPlugins($enabledOnly);
		return PluginVariable::populateVariables($plugins);
	}
}
