<?php
namespace Blocks;

/**
 * Settings functions
 */
class SettingsVariable
{
	private $_settings;

	/**
	 * Caches the setting categories for use between __isset() and __get()
	 *
	 * @param string $category
	 * @return array
	 */
	private function getSettings($category)
	{
		if (!isset($this->_settings[$category]))
		{
			$settings = blx()->settings->getSystemSettings($category);
			$this->_settings[$category] = $settings;
		}

		return $this->_settings[$category];
	}

	/**
	 * Returns whether a setting category exists.
	 *
	 * @param $category
	 * @return bool
	 */
	public function __isset($category)
	{
		$settings = $this->getSettings($category);
		return !empty($settings);
	}

	/**
	 * Returns the system settings for a category.
	 *
	 * @param $category
	 * @return array
	 */
	public function __get($category)
	{
		$settings = $this->getSettings($category);
		return $settings;
	}
}
