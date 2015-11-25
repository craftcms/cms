<?php
namespace Craft;

/**
 * Plugin functions.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.variables
 * @since     1.0
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

	/**
	 * Returns a given plugin’s icon URL.
	 *
	 * @param string $pluginHandle The plugin’s class handle
	 * @param int    $size         The size of the icon
	 *
	 * @return string
	 */
	public function getPluginIconUrl($pluginHandle, $size = 100)
	{
		return craft()->plugins->getPluginIconUrl($pluginHandle, $size);
	}
}
