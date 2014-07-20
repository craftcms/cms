<?php
namespace Craft;

/**
 * Class Exception
 *
 * @package craft.app.etc.errors
 */
class Exception extends \CException
{
	/**
	 * @param     $message
	 * @param int $code
	 */
	function __construct($message, $code = 0)
	{
		Craft::log($message, LogLevel::Error);
		parent::__construct($message, $code);
	}
}
