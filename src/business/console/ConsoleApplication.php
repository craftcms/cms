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
			'business.*',
			'business.console.*',
			'business.console.commands.*',
			'business.datetime.*',
			'business.db.*',
			'business.email.*',
			'business.enums.*',
			'business.exceptions.*',
			'business.logging.*',
			'business.services.*',
			'business.updates.*',
			'business.utils.*',
			'business.validators.*',
			'controllers.*',
			'migrations.*',
			'models.*',
			'models.forms.*',
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
