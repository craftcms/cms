<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;
use craft\app\services\Config as ConfigService;

/**
 * Class Config variable.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 * @deprecated in 3.0
 */
class Config
{
    // Public Methods
    // =========================================================================

    /**
     * Returns whether a config item exists.
     *
     * @param string $name
     *
     * @return boolean
     */
    public function __isset($name)
    {
        return Craft::$app->getConfig()->exists($name, ConfigService::CATEGORY_GENERAL);
    }

    /**
     * Returns a config item.
     *
     * @param string $name
     *
     * @return string
     */
    public function __get($name)
    {
        Craft::$app->getDeprecator()->log('craft.config.[setting]', 'craft.config.[setting] has been deprecated. Use craft.app.config.get(\'setting\') instead.');

        return Craft::$app->getConfig()->get($name, ConfigService::CATEGORY_GENERAL);
    }

    /**
     * Returns a config item from the specified config file.
     *
     * @param string $name
     * @param string $file
     *
     * @return mixed
     */
    public function get($name, $file = 'general')
    {
        Craft::$app->getDeprecator()->log('craft.config.get()', 'craft.config.get() has been deprecated. Use craft.app.config.get() instead.');

        return Craft::$app->getConfig()->get($name, $file);
    }

    /**
     * Returns whether generated URLs should be formatted using PATH_INFO.
     *
     * @return boolean
     */
    public function usePathInfo()
    {
        Craft::$app->getDeprecator()->log('craft.config.usePathInfo()', 'craft.config.usePathInfo() has been deprecated. Use craft.app.config.usePathInfo() instead.');

        return Craft::$app->getConfig()->usePathInfo();
    }

    /**
     * Returns whether generated URLs should omit 'index.php'.
     *
     * @return boolean
     */
    public function omitScriptNameInUrls()
    {
        Craft::$app->getDeprecator()->log('craft.config.omitScriptNameInUrls()', 'craft.config.omitScriptNameInUrls() has been deprecated. Use craft.app.config.omitScriptNameInUrls() instead.');

        return Craft::$app->getConfig()->omitScriptNameInUrls();
    }

    /**
     * Returns the CP resource trigger word.
     *
     * @return string
     */
    public function getResourceTrigger()
    {
        Craft::$app->getDeprecator()->log('craft.config.getResourceTrigger()', 'craft.config.getResourceTrigger() has been deprecated. Use craft.app.config.getResourceTrigger() instead.');

        return Craft::$app->getConfig()->getResourceTrigger();
    }
}
