<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\helpers\App;
use craft\models\MailSettings;
use yii\base\Component;

/**
 * System Settings service.
 * An instance of the System Settings service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getSystemSettings()|`Craft::$app->systemSettings`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 3.1.0. Use [[\craft\services\ProjectConfig]] instead.
 */
class SystemSettings extends Component
{
    /**
     * @var array
     * @deprecated in 3.1.0
     */
    public $defaults;

    /**
     * Returns the system settings for a category.
     *
     * @param string $category
     * @return array
     * @deprecated in 3.1.0. Use [[\craft\services\ProjectConfig::get()]] instead.
     */
    public function getSettings(string $category): array
    {
        return Craft::$app->getProjectConfig()->get($category) ?? [];
    }

    /**
     * Returns an individual system setting.
     *
     * @param string $category
     * @param string $key
     * @return mixed
     * @deprecated in 3.1.0. Use [[\craft\services\ProjectConfig::get()]] instead.
     */
    public function getSetting(string $category, string $key)
    {
        return Craft::$app->getProjectConfig()->get($category . '.' . $key);
    }

    /**
     * Saves the system settings for a category.
     *
     * @param string $category
     * @param array|null $settings
     * @return bool Whether the new settings saved
     * @deprecated in 3.1.0. Use [[\craft\services\ProjectConfig::set()]] instead.
     */
    public function saveSettings(string $category, array $settings = null): bool
    {
        Craft::$app->getProjectConfig()->set($category, $settings);
        return true;
    }

    /**
     * Returns the email settings.
     *
     * @return MailSettings
     * @deprecated in 3.1.0. Use [[\craft\helpers\App::mailSettings()]] instead.
     */
    public function getEmailSettings(): MailSettings
    {
        return App::mailSettings();
    }
}
