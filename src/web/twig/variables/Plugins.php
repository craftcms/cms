<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\variables;

use craft\app\base\Plugin;
use craft\app\base\PluginInterface;

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
	 * Returns info about all of the plugins saved in craft/plugins, whether they’re installed or not.
	 *
	 * @return array Info about all of the plugins saved in craft/plugins
	 */
	public function getPluginInfo()
	{
		return \Craft::$app->getPlugins()->getPluginInfo();
	}
}
