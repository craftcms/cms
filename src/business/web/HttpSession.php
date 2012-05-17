<?php
namespace Blocks;

/**
 * Extends CHttpSession to add support for setting the session folder and creating it if it doesn't exist.
 */
class HttpSession extends \CHttpSession
{
	/**
	 *
	 */
	public function init()
	{
		$this->setSavePath(b()->path->getSessionPath());
		parent::init();
	}
}
