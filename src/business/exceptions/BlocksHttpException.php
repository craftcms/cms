<?php

class BlocksHttpException extends CHttpException
{
	public function __construct($status, $message = null, $code = 0)
	{
		Blocks::log($status.' - '.$message);
		parent::__construct($status);
	}
}
