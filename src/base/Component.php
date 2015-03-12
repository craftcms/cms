<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

/**
 * ComponentTrait implements the common methods and properties for Craft component classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class Component extends Model implements ComponentInterface
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the display name of this class.
	 * @return string The display name of this class.
	 */
	public static function classDisplayName()
	{
		$classNameParts = implode('\\', static::className());
		$displayName = array_pop($classNameParts);
		return $displayName;
	}

	/**
	 * @inheritdoc
	 */
	public static function classHandle()
	{
		$classNameParts = implode('\\', static::className());
		$handle = array_pop($classNameParts);
		return strtolower($handle);
	}
}
