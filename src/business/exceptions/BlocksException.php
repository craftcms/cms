<?php

class BlocksException extends CException
{
	public function __construct($message, $code = 0)
	{
		Blocks::log($message);
		parent::__construct($message, $code);
	}
}
