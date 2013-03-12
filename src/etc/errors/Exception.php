<?php
namespace Craft;

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
		Craft::log($message, \CLogger::LEVEL_ERROR);
		parent::__construct($message, $code);
	}
}
