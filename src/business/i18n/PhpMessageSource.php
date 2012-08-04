<?php
namespace Blocks;

/**
 *
 */
class PhpMessageSource extends \CPhpMessageSource
{
	public function init()
	{
		$this->forceTranslation = true;
		$this->basePath = blx()->path->getLanguagesPath();
		parent::init();
	}
}
