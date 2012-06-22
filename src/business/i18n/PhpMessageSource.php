<?php
namespace Blocks;

/**
 *
 */
class PhpMessageSource extends \CPhpMessageSource
{
	public function init()
	{
		$this->basePath = blx()->path->getLanguagesPath();
		parent::init();
	}
}
