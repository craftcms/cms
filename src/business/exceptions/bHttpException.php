<?php

/**
 *
 */
class bHttpException extends CHttpException
{
	/**
	 * @param      $status
	 * @param null $message
	 * @param int  $code
	 */
	public function __construct($status, $message = null, $code = 0)
	{
		Blocks::log($status.' - '.$message);
		parent::__construct($status, $message, $code);
	}
}
