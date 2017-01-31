<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\twig\variables;

use Craft;
use craft\services\Config as ConfigService;

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

    /** @noinspection MagicMethodsValidityInspection */
    /**
     * Returns whether a config item exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return Craft::$app->getConfig()->exists($name, ConfigService::CATEGORY_GENERAL);
    }

    /** @noinspection MagicMethodsValidityInspection */
    /**
     * Returns a config item.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
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
    public function get(string $name, string $file = 'general')
    {
        Craft::$app->getDeprecator()->log('craft.config.get()', 'craft.config.get() has been deprecated. Use craft.app.config.get() instead.');

        return Craft::$app->getConfig()->get($name, $file);
    }

    /**
     * Returns whether generated URLs should be formatted using PATH_INFO.
     *
     * @return bool
     */
    public function usePathInfo(): bool
    {
        Craft::$app->getDeprecator()->log('craft.config.usePathInfo()', 'craft.config.usePathInfo() has been deprecated. Use craft.app.config.usePathInfo instead.');

        return Craft::$app->getConfig()->getUsePathInfo();
    }

    /**
     * Returns whether generated URLs should omit 'index.php'.
     *
     * @return bool
     */
    public function omitScriptNameInUrls(): bool
    {
        Craft::$app->getDeprecator()->log('craft.config.omitScriptNameInUrls()', 'craft.config.omitScriptNameInUrls() has been deprecated. Use craft.app.config.omitScriptNameInUrls instead.');

        return Craft::$app->getConfig()->getOmitScriptNameInUrls();
    }

    /**
     * Returns the CP resource trigger word.
     *
     * @return string
     */
    public function getResourceTrigger(): string
    {
        Craft::$app->getDeprecator()->log('craft.config.getResourceTrigger()', 'craft.config.getResourceTrigger() has been deprecated. Use craft.app.config.resourceTrigger instead.');

        return Craft::$app->getConfig()->getResourceTrigger();
    }
}
