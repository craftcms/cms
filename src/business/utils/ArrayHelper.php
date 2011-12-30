<?php

class ArrayHelper
{
	/**
	 * Flattens a multi-dimensional array into a single-dimensional array
	 */
	public static function flattenArray($arr, $prefix = '')
	{
		$flattened = array();

		foreach ($arr as $key => $value)
		{
			if ($prefix) $key = "{$prefix}[{$key}]";

			if (is_array($value))
			{
				$flattened = array_merge($flattened, flatten_settings($value, $key));
			}
			else
			{
				$flattened[$key] = $value;
			}
		}

		return $flattened;
	}

	/**
	 * Expands a flattened array back into its original form
	 */
	public static function expandArray($arr)
	{
		$expanded = array();

		foreach ($arr as $key => $value)
		{
			// is this an array element?
			if (preg_match('/^(\w+)(\[.*)/', $key, $m))
			{
				$key = '$expanded["'.$m[1].'"]' . preg_replace('/\[([a-zA-Z]\w*?)\]/', "[\"$1\"]", $m[2]);
				eval($key.' = "'.addslashes($value).'";');
			}
			else
			{
				$expanded[$key] = $value;
			}
		}

		return $expanded;
	}

	/**
	 * 
	 */
	public static function expandSettingsArray($settings)
	{
		$arr = array();

		foreach ($settings as $setting)
		{
			$arr[$setting->key] = $setting->value;
		}

		return self::expandArray($arr);
	}
}
