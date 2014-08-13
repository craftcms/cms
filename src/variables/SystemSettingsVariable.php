<?php
namespace Craft;

/**
 * Settings functions.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.variables
 * @since     1.0
 */
class SystemSettingsVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns whether a setting category exists.
	 *
	 * @param string $category
	 *
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
	 *
	 * @return array
	 */
	public function __get($category)
	{
		return craft()->systemSettings->getSettings($category);
	}
}
