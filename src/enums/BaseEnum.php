<?php

namespace Craft;

abstract class BaseEnum
{
	private static $_constants = null;

	public static function isValidName($name, $strict = false)
	{
		$constants = static::_getConstants();

		if ($strict)
		{
			return array_key_exists($name, $constants);
		}

		$keys = array_map('strtolower', array_keys($constants));
		return in_array(strtolower($name), $keys);
	}

	public static function isValidValue($value, $strict = false)
	{
		$values = array_values(static::_getConstants());
		return in_array($value, $values, $strict);
	}

	private static function _getConstants()
	{
		if (static::$_constants === null)
		{
			$reflect = new ReflectionClass(get_called_class());
			static::$_constants = $reflect->getConstants();
		}

		return static::$_constants;
	}
} 
