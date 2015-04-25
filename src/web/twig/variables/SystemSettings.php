<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\variables;

/**
 * Settings functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SystemSettings
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
		return \Craft::$app->getSystemSettings()->getSettings($category);
	}
}
