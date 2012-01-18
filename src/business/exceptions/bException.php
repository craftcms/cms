<?php

/**
 *
 */
class bException extends CException
{
	/**
	 * @param     $message
	 * @param int $code
	 */
	public function __construct($message, $code = 0)
	{
		Blocks::log($message);
		parent::__construct($message, $code);
	}
}
