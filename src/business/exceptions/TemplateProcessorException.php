<?php
namespace Blocks;

/**
 *
 */
class TemplateProcessorException extends Exception
{
	/**
	 * @param $message
	 * @param $templateFileName
	 * @param $lineNumber
	 */
	function __construct($message, $templateFileName, $lineNumber)
	{
		$this->file = $templateFileName;
		$this->line = (int)$lineNumber;

		Blocks::log($message);
		parent::__construct($message, null, null);
	}
}
