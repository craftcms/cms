<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\services\Config as ConfigService;
use yii\base\InvalidArgumentException;

/**
 * Class Config variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 3.0.0
 */
class Config
{
    /** @noinspection MagicMethodsValidityInspection */
    /**
     * Returns whether a config item exists.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset(Craft::$app->getConfig()->getGeneral()->$name);
    }

    /** @noinspection MagicMethodsValidityInspection */
    /**
     * Returns a config item.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        Craft::$app->getDeprecator()->log('craft.config.[setting]', '`craft.config.[setting]` has been deprecated. Use `craft.app.config.general.[setting]` instead.');

        return Craft::$app->getConfig()->getGeneral()->$name ?? null;
    }

    /**
     * Returns a config item from the specified config file.
     *
     * @param string $name
     * @param string $category
     * @return mixed
     */
    public function get(string $name, string $category = ConfigService::CATEGORY_GENERAL)
    {
        Craft::$app->getDeprecator()->log('craft.config.get()', '`craft.config.get()` has been deprecated. Use `craft.app.config.general.[setting]` instead.');

        try {
            return Craft::$app->getConfig()->getConfigSettings($category)->$name ?? null;
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Returns whether generated URLs should be formatted using PATH_INFO.
     *
     * @return bool
     */
    public function usePathInfo(): bool
    {
        Craft::$app->getDeprecator()->log('craft.config.usePathInfo()', '`craft.config.usePathInfo()` has been deprecated. Use `craft.app.config.general.usePathInfo` instead.');

        return Craft::$app->getConfig()->getGeneral()->usePathInfo;
    }

    /**
     * Returns whether generated URLs should omit 'index.php'.
     *
     * @return bool
     */
    public function omitScriptNameInUrls(): bool
    {
        Craft::$app->getDeprecator()->log('craft.config.omitScriptNameInUrls()', '`craft.config.omitScriptNameInUrls()` has been deprecated. Use `craft.app.config.general.omitScriptNameInUrls` instead.');

        return Craft::$app->getConfig()->getGeneral()->omitScriptNameInUrls;
    }
}
