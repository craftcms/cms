<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\ParseConfigEvent;
use craft\helpers\Json;
use craft\models\MailSettings;
use craft\records\SystemSettings as SystemSettingsRecord;
use yii\base\Component;

/**
 * System Settings service.
 * An instance of the System Settings service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getSystemSettings()|`Craft::$app->systemSettings`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SystemSettings extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var
     */
    public $defaults;

    /**
     * @var
     */
    private $_settingsRecords;

    // Public Methods
    // =========================================================================

    /**
     * Returns the system settings for a category.
     *
     * @param string $category
     * @return array
     */
    public function getSettings(string $category): array
    {
        $record = $this->_getSettingsRecord($category);

        if ($record !== null) {
            $settings = Json::decode($record->settings);
        } else {
            $settings = [];
        }

        if (isset($this->defaults[$category])) {
            $settings = array_merge($this->defaults[$category], $settings);
        }

        return $settings;
    }

    /**
     * Returns an individual system setting.
     *
     * @param string $category
     * @param string $key
     * @return mixed
     */
    public function getSetting(string $category, string $key)
    {
        $settings = $this->getSettings($category);

        if (isset($settings[$key])) {
            return $settings[$key];
        }

        return null;
    }

    /**
     * Saves the system settings for a category.
     *
     * @param string $category
     * @param array|null $settings
     * @return bool Whether the new settings saved
     */
    public function saveSettings(string $category, array $settings = null): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $configPath = 'settings.'.$category;

        $projectConfig->save($configPath, $settings);

        return $projectConfig->processConfigChanges($configPath);
    }

    /**
     * Returns the email settings.
     *
     * @return MailSettings
     */
    public function getEmailSettings(): MailSettings
    {
        $settings = $this->getSettings('email');

        return new MailSettings($settings);
    }

    /**
     * Handle system setting configuration change
     *
     * @param ParseConfigEvent $event
     */
    public function handleChangedSettings(ParseConfigEvent $event) {
        $path = $event->configPath;

        if (preg_match('/settings\.([a-z0-9]+)$/i', $path, $matches)) {
            $settings = Craft::$app->getProjectConfig()->get($path, true);
            $category = $matches[1];

            $record = $this->_getSettingsRecord($category);

            if ($record === null) {
                $record = new SystemSettingsRecord();
                $record->category = $category;
                $this->_settingsRecords[$category] = $record;
            }

            $record->settings = $settings;
            $record->save();
        }
    }

    /**
     * Handle system settings getting deleted.
     *
     * @param ParseConfigEvent $event
     */
    public function handleDeletedSettings(ParseConfigEvent $event) {
        $path = $event->configPath;

        if (preg_match('/settings\.([a-z0-9]+)$/i', $path, $matches)) {
            $settings = Craft::$app->getProjectConfig()->get($path, true);
            $category = $matches[1];

            $record = $this->_getSettingsRecord($category);
            if ($record) {
                $record->delete();
                $this->_settingsRecords[$category] = false;
            }
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a SystemSettings record by its category.
     *
     * @param string $category
     * @return SystemSettingsRecord|null The SystemSettings record or null
     */
    private function _getSettingsRecord(string $category)
    {
        if (!isset($this->_settingsRecords[$category])) {
            $record = SystemSettingsRecord::findOne([
                'category' => $category
            ]);

            if ($record) {
                $this->_settingsRecords[$category] = $record;
            } else {
                $this->_settingsRecords[$category] = false;
            }
        }

        if ($this->_settingsRecords[$category] !== false) {
            return $this->_settingsRecords[$category];
        }

        return null;
    }
}
