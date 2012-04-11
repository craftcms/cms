<?php
namespace Blocks;

/**
 *
 */
class FileLogRoute extends \CFileLogRoute
{
	public function init()
	{
		$this->setLogFile('blocks.log');

		$this->levels = b()->config->devMode ? '' : 'error, warning';
		$this->filter = b()->config->devMode ? 'CLogFilter' : null;

		parent::init();
	}
}
