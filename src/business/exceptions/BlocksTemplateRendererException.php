<?php

/**
 *
 */
class BlocksTemplateRendererException extends BlocksException
{
	/**
	 * @access public
	 *
	 * @param $message
	 * @param $templateFileName
	 * @param $line
	 */
	public function __construct($message, $templateFileName, $line)
	{
		Blocks::log($message);
		parent::__construct("Invalid template: {$templateFileName}, at line {$line}. {$message}", null, null);
	}
}
