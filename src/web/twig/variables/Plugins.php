<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;

/**
 * Plugin functions.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 * @deprecated in 3.0
 */
class Plugins
{
    // Public Methods
    // =========================================================================

    /**
     * Constructor
     */
    public function __construct()
    {
        Craft::$app->getDeprecator()->log('craft.plugins', 'craft.plugins has been deprecated. Use craft.app.plugins instead.');
    }

    /**
     * Returns info about all of the plugins saved in craft/plugins, whether they’re installed or not.
     *
     * @return array Info about all of the plugins saved in craft/plugins
     */
    public function getPluginInfo()
    {
        return Craft::$app->getPlugins()->getPluginInfo();
    }

    /**
     * Returns a plugin’s SVG icon.
     *
     * @param string $pluginHandle The plugin’s handle
     *
     * @return string The plugin’s SVG icon
     */
    public function getPluginIconSvg($pluginHandle)
    {
        return Craft::$app->getPlugins()->getPluginIconSvg($pluginHandle);
    }
}
