<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

/**
 * Class ArrayHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ArrayHelper extends \yii\helpers\ArrayHelper
{
	// Public Methods
	// =========================================================================

	/**
	 * Converts an object, an array of objects, or a comma-delimited string into an array.
	 *
	 *     ArrayHelper::toArray('one, two, three') => ['one', 'two', 'three']
	 *
	 * @param array|object|string $object     The object, array or string to be converted into an array.
	 * @param array               $properties A mapping from object class names to the properties that need to put into
	 *                                        the resulting arrays. The properties specified for each class is an array
	 *                                        of the following format:
     *
     * ~~~
     * [
     *     'app\models\Post' => [
     *         'id',
     *         'title',
     *         // the key name in array result => property name
     *         'createTime' => 'created_at',
     *         // the key name in array result => anonymous function
     *         'length' => function ($post) {
     *             return strlen($post->content);
     *         },
     *     ],
     * ]
     * ~~~
     *
     * The result of `ArrayHelper::toArray($post, $properties)` could be like the following:
     *
     * ~~~
     * [
     *     'id' => 123,
     *     'title' => 'test',
     *     'createTime' => '2013-01-01 12:00AM',
     *     'length' => 301,
     * ]
     * ~~~
	 * @param bool                $recursive Whether to recursively converts properties which are objects into arrays.
	 *
	 * @return array The array representation of the given object.
	 */
	public static function toArray($object, $properties = [], $recursive = true)
	{
		if ($object === null)
		{
			return [];
		}
		else if (is_string($object))
		{
			// Split it on the non-escaped commas
			$object = preg_split('/(?<!\\\),/', $object);

			// Remove any of the backslashes used to escape the commas
			foreach ($object as $key => $val)
			{
				// Remove leading/trailing whitespace
				$val = trim($val);

				// Remove any backslashes used to escape commas
				$val = str_replace('\,', ',', $val);

				$object[$key] = $val;
			}

			// Remove any empty elements and reset the keys
			$object = array_merge(array_filter($object));

			return $object;
		}
		else
		{
			return parent::toArray($object, $properties, $recursive);
		}
	}

	/**
	 * Prepends or appends a value to an array.
	 *
	 * @param array &$arr
	 * @param mixed $value
	 *
	 * @param bool  $prepend
	 */
	public static function prependOrAppend(&$arr, $value, $prepend)
	{
		if ($prepend)
		{
			array_unshift($arr, $value);
		}
		else
		{
			array_push($arr, $value);
		}
	}

	/**
	 * Filters empty strings from an array.
	 *
	 * @param array $arr
	 *
	 * @return array
	 */
	public static function filterEmptyStringsFromArray($arr)
	{
		return array_filter($arr, ['\craft\app\helpers\ArrayHelper', '_isNotAnEmptyString']);
	}

	/**
	 * Returns the first value in a given array.
	 *
	 * @param array $arr
	 *
	 * @return mixed|null
	 */
	public static function getFirstValue($arr)
	{
		if (count($arr))
		{
			if (isset($arr[0]))
			{
				return $arr[0];
			}
			else
			{
				$keys = array_keys($arr);
				return $arr[$keys[0]];
			}
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * The array_filter() callback function for filterEmptyStringsFromArray().
	 *
	 * @param string $val
	 *
	 * @return bool
	 */
	private static function _isNotAnEmptyString($val)
	{
		return (mb_strlen($val) != 0);
	}
}
