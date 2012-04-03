<?php
namespace Blocks;

/**
 *
 */
class HttpException extends \CHttpException
{
	/**
	 * @param      $status
	 * @param null $message
	 * @param int  $code
	 */
	function __construct($status, $message = null, $code = 0)
	{
		Blocks::log($status.' - '.$message);
		parent::__construct($status, $message, $code);
	}
}
