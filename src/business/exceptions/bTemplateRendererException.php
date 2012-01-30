<?php

/**
 *
 */
class bTemplateRendererException extends bException
{
	/**
	 * @param $message
	 * @param $templateFileName
	 * @param $line
	 */
	public function __construct($message, $templateFileName)
	{
		Blocks::log($message);
		parent::__construct("Invalid template: {$templateFileName}. {$message}", null, null);
	}
}
