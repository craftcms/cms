<?php
namespace Blocks;

/**
 * Settings functions
 */
class SettingsVariable
{
	/**
	 * Returns the system settings for a category.
	 * @return array
	 */
	public function __get($category)
	{
		$settings = blx()->settings->getSystemSettings($category);
		return $settings;
	}
}
