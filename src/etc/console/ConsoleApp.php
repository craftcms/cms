<?php
namespace Craft;

/**
 *
 */
class ConsoleApp extends \CConsoleApplication
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
			Craft::import($alias);
		}

		craft()->getComponent('log');

		// Attach our Craft app behavior.
		$this->attachBehavior('AppBehavior', new AppBehavior());

		// Set our own custom runtime path.
		$this->setRuntimePath(craft()->path->getRuntimePath());

		// No need for these.
		craft()->log->removeRoute('WebLogRoute');
		craft()->log->removeRoute('ProfileLogRoute');

		// Load the plugins
		craft()->plugins->loadPlugins();

		// Call parent::init before the plugin console command logic so craft()->commandRunner will be available to us.
		parent::init();

		foreach (craft()->plugins->getPlugins() as $plugin)
		{
			$commandsPath = craft()->path->getPluginsPath().mb_strtolower($plugin->getClassHandle()).'/consolecommands/';
			if (IOHelper::folderExists($commandsPath))
			{
				craft()->commandRunner->addCommands(rtrim($commandsPath, '/'));
			}
		}
	}

	/**
	 * @return ConsoleCommandRunner
	 */
	protected function createCommandRunner()
	{
		return new ConsoleCommandRunner();
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
