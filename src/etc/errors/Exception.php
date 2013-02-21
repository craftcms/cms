<?php
namespace Blocks;

/**
 *
 */
class Exception extends \CException
{
	/**
	 * @param     $message
	 * @param int $code
	 */
	function __construct($message, $code = 0)
	{
		Blocks::log($message, \CLogger::LEVEL_ERROR);
		parent::__construct($message, $code);
	}
}
