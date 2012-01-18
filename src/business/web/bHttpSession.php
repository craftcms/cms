<?php
/**
 * Extends CHttpSession to add support for setting the session folder and creating it if it doesn't exist.
 */
class bHttpSession extends CHttpSession
{

	public function init()
	{
		$this->setSavePath(Blocks::app()->path->sessionPath);
		parent::init();
	}
}
