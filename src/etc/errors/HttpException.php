<?php
namespace Craft;

/**
 * Class HttpException
 *
 * @package craft.app.etc.errors
 */
class HttpException extends \CHttpException
{
	/**
	 * @param      $status
	 * @param null $message
	 * @param int  $code
	 */
	function __construct($status = '', $message = null, $code = 0)
	{
		$status = $status ? $status : '';
		Craft::log(($status ? $status.' - ' : '').$message, LogLevel::Warning);
		parent::__construct($status, $message, $code);
	}
}
