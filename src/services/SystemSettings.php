<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use craft\helpers\Json;
use craft\models\MailSettings;
use craft\records\SystemSettings as SystemSettingsRecord;
use yii\base\Component;

/**
 * System Settings service.
 * An instance of the System Settings service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getSystemSettings()|<code>Craft::$app->systemSettings</code>]].
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
        $record = $this->_getSettingsRecord($category);

        if ($record === null) {
            // If there are no new settings, we're already done
            if (!$settings) {
                return true;
            }

            // Create a new SystemSettings record, and save a reference to it
            $record = new SystemSettingsRecord();
            $record->category = $category;
            $this->_settingsRecords[$category] = $record;
        } else if (!$settings) {
            // Delete the record
            $record->delete();
            $this->_settingsRecords[$category] = false;

            return true;
        }

        $record->settings = $settings;
        $record->save();

        return !$record->hasErrors();
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
