<?php

/**
 *
 */
class BlocksException extends CException
{
	/**
	 * @access public
	 *
	 * @param     $message
	 * @param int $code
	 */
	public function __construct($message, $code = 0)
	{
		Blocks::log($message);
		parent::__construct($message, $code);
	}
}
