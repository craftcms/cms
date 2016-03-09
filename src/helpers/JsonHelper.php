<?php
namespace Craft;

/**
 * Class JsonHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class JsonHelper extends \CJSON
{
	// Public Methods
	// =========================================================================

	/**
	 * @return null
	 */
	public static function sendJsonHeaders()
	{
		HeaderHelper::setNoCache();
		HeaderHelper::setContentTypeByExtension('json');
	}

	/**
	 * Will remove single-line, multi-line, //, /*, comments from JSON
	 * (since comments technically product invalid JSON).
	 *
	 * @param $json
	 *
	 * @return mixed|string
	 */
	public static function removeComments($json)
	{
		// Remove any comments from the JSON.
		// Adapted from http://stackoverflow.com/a/31907095/684
		$pattern = '/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/';

		$json = preg_replace($pattern, '' , $json);
		$json = trim($json, PHP_EOL);

		return $json;
	}
}
