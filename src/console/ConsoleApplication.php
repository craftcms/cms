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
}
