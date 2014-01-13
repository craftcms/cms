<?php
namespace Craft;

/**
 *
 */
class SystemSettingsService extends BaseApplicationComponent
{
	private $_settings;

	/**
	 * Returns the system settings for a category.
	 *
	 * @param string $category
	 * @return array
	 */
	public function getSettings($category)
	{
		$settings = $this->_getSettings($category);

		if ($settings)
		{
			return $settings->settings;
		}
		else
		{
			return array();
		}
	}

	/**
	 * Return the DateTime for when the category was last updated.
	 *
	 * @param $category
	 * @return null|DateTime
	 */
	public function getCategoryTimeUpdated($category)
	{
		// Ensure fresh data.
		unset($this->_settings[$category]);

		$settings = $this->_getSettings($category);
		if ($settings)
		{
			return $settings->dateUpdated;
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
	 * @param mixed $default
	 * @return mixed
	 */
	public function getSetting($category, $key, $default = null)
	{
		$settings = $this->getSettings($category);

		if (isset($settings[$key]))
		{
			return $settings[$key];
		}
		else
		{
			return $default;
		}
	}

	/**
	 * Saves the system settings for a category.
	 *
	 * @param string $category
	 * @param array $settings
	 * @return bool Whether the new settings saved
	 */
	public function saveSettings($category, $settings = null)
	{
		$record = $this->_getSettings($category);

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
			$this->_settings[$category] = $record;
		}
		else if (!$settings)
		{
			// Delete the record
			$record->delete();
			$this->_settings[$category] = false;

			return true;
		}

		$record->settings = $settings;
		$record->save();

		return !$record->hasErrors();
	}

	/**
	 * Returns a SystemSettings record by its category.
	 *
	 * @access private
	 * @param string $category
	 * @return mixed The SystemSettings record or false
	 */
	private function _getSettings($category)
	{
		if (!isset($this->_settings[$category]))
		{
			$settings = SystemSettingsRecord::model()->findByAttributes(array(
				'category' => $category
			));

			if ($settings)
			{
				$this->_settings[$category] = $settings;
			}
			else
			{
				$this->_settings[$category] = false;
			}
		}

		return $this->_settings[$category];
	}
}
