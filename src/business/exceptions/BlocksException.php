<?php

class BlocksException extends CException
{
	public function __construct($message, $code = null, $previous = null)
	{
		Blocks::log($message);
		parent::__construct($message, $code, $previous);
	}
}
