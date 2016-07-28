<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;
use craft\app\enums\ConfigCategory;

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
     * Constructor
     */
    public function __construct()
    {
        Craft::$app->getDeprecator()->log('craft.config', 'craft.config has been deprecated. Use craft.app.config instead.');
    }

    /**
     * Returns whether a config item exists.
     *
     * @param string $name
     *
     * @return boolean
     */
    public function __isset($name)
    {
        return Craft::$app->getConfig()->exists($name, ConfigCategory::General);
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
        return Craft::$app->getConfig()->get($name, ConfigCategory::General);
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
        return Craft::$app->getConfig()->get($name, $file);
    }

    /**
     * Returns whether generated URLs should be formatted using PATH_INFO.
     *
     * @return boolean
     */
    public function usePathInfo()
    {
        return Craft::$app->getConfig()->usePathInfo();
    }

    /**
     * Returns whether generated URLs should omit 'index.php'.
     *
     * @return boolean
     */
    public function omitScriptNameInUrls()
    {
        return Craft::$app->getConfig()->omitScriptNameInUrls();
    }

    /**
     * Returns the CP resource trigger word.
     *
     * @return string
     */
    public function getResourceTrigger()
    {
        return Craft::$app->getConfig()->getResourceTrigger();
    }
}
