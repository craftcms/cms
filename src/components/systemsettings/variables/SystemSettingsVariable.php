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
}
