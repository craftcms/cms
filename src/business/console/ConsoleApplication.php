<?php
namespace Blocks;

/**
 *
 */
class ConsoleApplication extends \CConsoleApplication
{

	public function init()
	{
		$this->_importClasses();
		parent::init();
	}

	/**
	 * Prepares Yii's autoloader with a map pointing all of Blocks' class names to their file paths
	 */
	private function _importClasses()
	{
		$aliases = array(
			'app.business.*',
			'app.business.console.*',
			'app.business.console.commands.*',
			'app.business.datetime.*',
			'app.business.db.*',
			'app.business.email.*',
			'app.business.enums.*',
			'app.business.exceptions.*',
			'app.business.logging.*',
			'app.business.services.*',
			'app.business.updates.*',
			'app.business.utils.*',
			'app.business.validators.*',
			'app.controllers.*',
			'app.migrations.*',
			'app.models.*',
			'app.models.forms.*',
		);

		foreach ($aliases as $alias)
		{
			Blocks::import($alias);
		}
	}

	/**
	 * @return ConsoleCommandRunner
	 */
	protected function createCommandRunner()
	{
		return new ConsoleCommandRunner();
	}
}
