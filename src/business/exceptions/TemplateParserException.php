<?php
namespace Blocks;

/**
 *
 */
class TemplateParserException extends Exception
{
	/**
	 * @param $message
	 * @param $lineNumber
	 */
	public function __construct($message, $lineNumber)
	{
		$this->line = (int)$lineNumber;

		Blocks::log($message.' on line '.$lineNumber);
		parent::__construct($message, null, null);
	}
}
