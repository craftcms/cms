<?php

class BlocksViewRendererException extends BlocksException
{
	public function __construct($message, $templateFileName, $line)
	{
		Blocks::log($message);
		parent::__construct("Invalid view template: {$templateFileName}, at line {$line}. {$message}", null, null);
	}
}
