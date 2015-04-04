<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use yii\base\InvalidParamException;

/**
 * Class JsonHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class JsonHelper extends \yii\helpers\Json
{
	// Public Methods
	// =========================================================================

	/**
	 * Decodes the given JSON string into a PHP data structure, only if the string is valid JSON.
	 *
	 * @param string $str The string to be decoded, if it's valid JSON.
	 * @param boolean $asArray Whether to return objects in terms of associative arrays.
	 * @return mixed The PHP data, or the given string if it wasn’t valid JSON.
	 */
	public static function encodeIfJson($str, $asArray = true)
	{
		try
		{
			return static::encode($str, $asArray);
		}
		catch (InvalidParamException $e)
		{
			// Wasn't JSON
			return $str;
		}
	}

	/**
	 * Sets JSON helpers on the response.
	 *
	 * @return null
	 */
	public static function sendJsonHeaders()
	{
		HeaderHelper::setNoCache();
		HeaderHelper::setContentTypeByExtension('json');
	}
}
