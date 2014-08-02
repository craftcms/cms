<?php
namespace Craft;

/**
 * Class JsonHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class JsonHelper extends \CJSON
{
	/**
	 * @return void
	 */
	public static function sendJsonHeaders()
	{
		// TODO: After next breakpoint release, replace with HeaderHelper code below
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-Type: application/json; charset=utf-8');

		//HeaderHelper::setNoCache();
		//HeaderHelper::setContentTypeByExtension('json');
	}
}
