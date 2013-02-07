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
		// Set default timezone to UTC
		date_default_timezone_set('UTC');

		foreach ($this->componentAliases as $alias)
		{
			Blocks::import($alias);
		}

		blx()->getComponent('log');

		// No need for these.
		blx()->log->removeRoute('WebLogRoute');
		blx()->log->removeRoute('ProfileLogRoute');

		parent::init();
	}

	/**
	 * @return ConsoleCommandRunner
	 */
	protected function createCommandRunner()
	{
		return new ConsoleCommandRunner();
	}

	/**
	 * @return bool
	 */
	public function isInstalled()
	{
		return true;
	}

	/**
	 * Returns whether we are executing in the context on a console app.
	 *
	 * @return bool
	 */
	public function isConsole()
	{
		return true;
	}
}
