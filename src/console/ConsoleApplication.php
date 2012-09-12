<?php
namespace Blocks;

/**
 *
 */
class ConsoleApplication extends \CConsoleApplication
{

	/**
	 *
	 */
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
			'app.*',
			'app.console.*',
			'app.console.commands.*',
			'app.components.datetime.*',
			'app.components.db.*',
			'app.components.email.*',
			'app.components.enums.*',
			'app.components.logging.*',
			'app.components.updates.*',
			'app.components.core.helpers.*',
			'app.components.core.validators.*',
			'app.migrations.*',
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
