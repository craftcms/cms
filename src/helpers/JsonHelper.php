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
}
