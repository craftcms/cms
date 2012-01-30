<?php
namespace Blocks;

/**
 *
 */
class Json extends \CJSON
{
	public static function sendJsonHeaders()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');
	}
}
