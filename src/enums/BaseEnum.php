<?php

namespace Craft;

/**
 * The BaseEnum class is an abstract class that all enums in Craft inherit. It provides some functionality that mimics
 * first-class citizen enum support in PHP.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.enums
 * @since     2.0
 */
abstract class BaseEnum
{
	// Properties
	// =========================================================================

	/**
	 * Holds the reflected constants for the enum.
	 *
	 * @var array|null
	 */
	private static $_constants = array();

	// Public Methods
	// =========================================================================

	/**
	 * Checks to see if the given name is valid in the enum.
	 *
	 * @param      $name   The name to search for.
	 * @param bool $strict Defaults to false. If set to true, will do a case sensitive search for the name.
	 *
	 * @return bool true if it is a valid name, false otherwise.
	 */
	public static function isValidName($name, $strict = false)
	{
		$constants = static::_getConstants();

		if ($strict)
		{
			return array_key_exists($name, $constants);
		}

		$keys = array_map(array('Craft\StringHelper', 'toLowerCase'), array_keys($constants));
		return in_array(StringHelper::toLowerCase($name), $keys);
	}

	/**
	 * Checks to see if the given value is valid in the enum.
	 *
	 * @param      $value  The value to search for.
	 * @param bool $strict Defaults to false. If set the true, will do a case sensitive search for the value.
	 *
	 * @return bool true if it is a valid value, false otherwise.
	 */
	public static function isValidValue($value, $strict = false)
	{
		$values = array_values(static::_getConstants());
		return in_array($value, $values, $strict);
	}

	/**
	 * @return array|null
	 */
	public static function getConstants()
	{
		return static::_getConstants();
	}

	// Private Methods
	// =========================================================================

	/**
	 * @return null
	 */
	private static function _getConstants()
	{
		$class = get_called_class();

		// static:: chokes PHP here because PHP sucks.
		if (!isset(self::$_constants[$class]))
		{
			$reflect = new \ReflectionClass($class);
			self::$_constants[$class] = $reflect->getConstants();
		}

		return self::$_constants[$class];
	}
}
