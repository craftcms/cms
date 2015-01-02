<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

/**
 * Class JsonHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
