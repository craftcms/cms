<?php
namespace Craft;

/**
 *
 */
class JsonHelper extends \CJSON
{
	/**
	 * @static
	 */
	public static function sendJsonHeaders()
	{
		HeaderHelper::setNoCache();
		HeaderHelper::setContentTypeByExtension('json');
	}
}
