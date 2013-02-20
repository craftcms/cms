<?php
namespace Blocks;

/**
 * Settings functions
 */
class SystemSettingsVariable
{
	/**
	 * Returns whether a setting category exists.
	 *
	 * @param string $category
	 * @return bool
	 */
	public function __isset($category)
	{
		return true;
	}

	/**
	 * Returns the system settings for a category.
	 *
	 * @param string $category
	 * @return array
	 */
	public function __get($category)
	{
		return blx()->systemSettings->getSettings($category);
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
		return blx()->systemSettings->getSetting($category, $key, $default);
	}
}
