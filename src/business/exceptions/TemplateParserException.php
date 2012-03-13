<?php
namespace Blocks;

/**
 *
 */
class TemplateParserException extends Exception
{
	/**
	 * @param $message
	 * @param $templateFileName
	 */
	public function __construct($message, $templateFileName)
	{
		Blocks::log($message);
		parent::__construct("Invalid template: {$templateFileName}. {$message}", null, null);
	}
}
