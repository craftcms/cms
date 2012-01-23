<?php

/**
 *
 */
class bArrayHelper
{
	/**
	 * Flattens a multi-dimensional array into a single-dimensional array
	 * @param        $arr
	 * @param string $prefix
	 * @return array
	 */
	public static function flattenArray($arr, $prefix = null)
	{
		$flattened = array();

		foreach ($arr as $key => $value)
		{
			if ($prefix !== null)
				$key = "{$prefix}[{$key}]";

			if (is_array($value))
			{
				$flattened = array_merge($flattened, self::flattenArray($value, $key));
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
	 * @param $arr
	 * @return array
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
	 * @param $settings
	 * @return array
	 */
	public static function expandSettingsArray($settings)
	{
		$arr = array();

		foreach ($settings as $setting)
			$arr[$setting->key] = $setting->value;

		return self::expandArray($arr);
	}
}
