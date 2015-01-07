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
class Plugins
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns a plugin by its class handle.
	 *
	 * @param string $class
	 * @param bool   $enabledOnly
	 *
	 * @return Plugin|null
	 */
	public function getPlugin($class, $enabledOnly = true)
	{
		$plugin = Craft::$app->plugins->getPlugin($class, $enabledOnly);

		if ($plugin)
		{
			return new Plugin($plugin);
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
		$plugins = Craft::$app->plugins->getPlugins($enabledOnly);
		return Plugin::populateVariables($plugins);
	}
}
