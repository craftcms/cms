<?php
namespace Craft;

/**
 * Class HtmlHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class HtmlHelper extends \CHtml
{
	/**
	 * Will take an HTML string and an associative array of key=>value pairs, HTML encode the values and swap them back
	 * into the original string using the keys as tokens.
	 *
	 * @param string $html      The HTML string.
	 * @param array  $variables An associative array of key => value pairs to be applied to the HTML string using `strtr`.
	 *
	 * @return string The HTML string with the encoded variable values swapped in.
	 */
	public static function encodeParams($html, $variables = array())
	{
		// Normalize the param keys
		$normalizedVariables = array();

		if (is_array($variables))
		{
			foreach ($variables as $key => $value)
			{
				$key = '{'.trim($key, '{}').'}';
				$normalizedVariables[$key] = static::encode($value);
			}

			$html = strtr($html, $normalizedVariables);
		}

		return $html;
	}
}
