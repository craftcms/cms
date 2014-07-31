<?php
namespace Craft;

/**
 * Class ConsoleApp
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.console
 * @since     1.0
 */
class ConsoleApp extends \CConsoleApplication
{
	public $componentAliases;

	/**
	 * Initializes the console app by creating the command runner.
	 *
	 * @return void
	 */
	public function init()
	{
		// Set default timezone to UTC
		date_default_timezone_set('UTC');

		foreach ($this->componentAliases as $alias)
		{
			Craft::import($alias);
		}

		// So we can try to translate Yii framework strings
		craft()->coreMessages->attachEventHandler('onMissingTranslation', array('Craft\LocalizationHelper', 'findMissingTranslation'));

		craft()->getComponent('log');

		// Attach our Craft app behavior.
		$this->attachBehavior('AppBehavior', new AppBehavior());

		// Set our own custom runtime path.
		$this->setRuntimePath(craft()->path->getRuntimePath());

		// Attach our own custom Logger
		Craft::setLogger(new Logger());

		// No need for these.
		craft()->log->removeRoute('WebLogRoute');
		craft()->log->removeRoute('ProfileLogRoute');

		// Load the plugins
		craft()->plugins->loadPlugins();

		// Validate some basics on the database configuration file.
		craft()->validateDbConfigFile();

		// Call parent::init before the plugin console command logic so craft()->commandRunner will be available to us.
		parent::init();

		foreach (craft()->plugins->getPlugins() as $plugin)
		{
			$commandsPath = craft()->path->getPluginsPath().StringHelper::toLowerCase($plugin->getClassHandle()).'/consolecommands/';
			if (IOHelper::folderExists($commandsPath))
			{
				craft()->commandRunner->addCommands(rtrim($commandsPath, '/'));
			}
		}
	}

	/**
	 * Attaches an event listener, or remembers it for later if the component has not been initialized yet.
	 *
	 * @param string $event
	 * @param mixed  $handler
	 *
	 * @return void
	 */
	public function on($event, $handler)
	{
		list($componentId, $eventName) = explode('.', $event, 2);

		$component = $this->getComponent($componentId);

		// Normalize the event name
		if (strncmp($eventName, 'on', 2) !== 0)
		{
			$eventName = 'on'.ucfirst($eventName);
		}

		$component->$eventName = $handler;
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
