<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use craft\app\dates\DateTime;
use craft\app\helpers\Json;
use craft\app\records\SystemSettings as SystemSettingsRecord;
use yii\base\Component;

/**
 * Class SystemSettings service.
 *
 * An instance of the SystemSettings service is globally accessible in Craft via [[Application::systemSettings `Craft::$app->getSystemSettings()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     *
     * @return array
     */
    public function getSettings($category)
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
     * Return the DateTime for when the category was last updated.
     *
     * @param $category
     *
     * @return null|DateTime
     */
    public function getCategoryTimeUpdated($category)
    {
        // Ensure fresh data.
        unset($this->_settingsRecords[$category]);

        $record = $this->_getSettingsRecord($category);

        if ($record !== null) {
            return $record->dateUpdated;
        }

        return null;
    }

    /**
     * Returns an individual system setting.
     *
     * @param string $category
     * @param string $key
     *
     * @return mixed
     */
    public function getSetting($category, $key)
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
     * @param array  $settings
     *
     * @return boolean Whether the new settings saved
     */
    public function saveSettings($category, $settings = null)
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

    // Private Methods
    // =========================================================================

    /**
     * Returns a SystemSettings record by its category.
     *
     * @param string $category
     *
     * @return SystemSettingsRecord|null The SystemSettings record or null
     */
    private function _getSettingsRecord($category)
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
