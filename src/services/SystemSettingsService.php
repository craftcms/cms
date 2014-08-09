<?php
namespace Craft;

/**
 * Class SystemSettingsService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class SystemSettingsService extends BaseApplicationComponent
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

		if ($record && is_array($record->settings))
		{
			$settings = $record->settings;
		}
		else
		{
			$settings = array();
		}

		if (isset($this->defaults[$category]))
		{
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

		if ($record)
		{
			return $record->dateUpdated;
		}
		else
		{
			return null;
		}

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

		if (isset($settings[$key]))
		{
			return $settings[$key];
		}
	}

	/**
	 * Saves the system settings for a category.
	 *
	 * @param string $category
	 * @param array  $settings
	 *
	 * @return bool Whether the new settings saved
	 */
	public function saveSettings($category, $settings = null)
	{
		$record = $this->_getSettingsRecord($category);

		if (!$record)
		{
			// If there are no new settings, we're already done
			if (!$settings)
			{
				return true;
			}

			// Create a new SystemSettings record, and save a reference to it
			$record = new SystemSettingsRecord();
			$record->category = $category;
			$this->_settingsRecords[$category] = $record;
		}
		else if (!$settings)
		{
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
	 * @return mixed The SystemSettings record or false
	 */
	private function _getSettingsRecord($category)
	{
		if (!isset($this->_settingsRecords[$category]))
		{
			$record = SystemSettingsRecord::model()->findByAttributes(array(
				'category' => $category
			));

			if ($record)
			{
				$this->_settingsRecords[$category] = $record;
			}
			else
			{
				$this->_settingsRecords[$category] = false;
			}
		}

		return $this->_settingsRecords[$category];
	}
}
