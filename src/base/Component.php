<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use yii\base\Arrayable;

/**
 * Component is the base class for classes representing Craft components in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class Component extends Model implements ComponentInterface, Arrayable
{
	// Static
	// =========================================================================

	/**
	 * Returns the display name of this class.
	 * @return string The display name of this class.
	 */
	public static function displayName()
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

	/**
	 * @inheritdoc
	 */
	public static function instantiate($config)
	{
		if ($config['type'])
		{
			$class = $config['type'];
			return new $class;
		}
		else
		{
			return new static;
		}
	}

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getType()
	{
		return static::className();
	}
}
