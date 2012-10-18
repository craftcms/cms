<?php
namespace Blocks;

/**
 *
 */
class ConsoleApplication extends \CConsoleApplication
{
	public $componentAliases;

	/**
	 *
	 */
	public function init()
	{
		foreach ($this->componentAliases as $alias)
		{
			Blocks::import($alias);
		}

		/*$aliases = array(
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
		);*/

		parent::init();
	}

	/**
	 * @return ConsoleCommandRunner
	 */
	protected function createCommandRunner()
	{
		return new ConsoleCommandRunner();
	}
}
