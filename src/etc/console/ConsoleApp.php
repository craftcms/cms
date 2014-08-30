<?php
namespace Craft;

/**
 * Class ConsoleApp
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.console
 * @since     1.0
 */
class ConsoleApp extends \CConsoleApplication
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	public $componentAliases;

	/**
	 * @var
	 */
	private $_pendingEvents;

	// Public Methods
	// =========================================================================

	/**
	 * Initializes the console app by creating the command runner.
	 *
	 * @return null
	 */
	public function init()
	{
		// Set default timezone to UTC
		date_default_timezone_set('UTC');

		// Import all the built-in components
		foreach ($this->componentAliases as $alias)
		{
			Craft::import($alias);
		}

		// Attach our Craft app behavior.
		$this->attachBehavior('AppBehavior', new AppBehavior());

		// Initialize Cache and LogRouter right away (order is important)
		$this->getComponent('cache');
		$this->getComponent('log');

		// So we can try to translate Yii framework strings
		$this->coreMessages->attachEventHandler('onMissingTranslation', array('Craft\LocalizationHelper', 'findMissingTranslation'));

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
	 * Attaches an event listener, or remembers it for later if the component
	 * has not been initialized yet.
	 *
	 * @param string $event
	 * @param mixed  $handler
	 *
	 * @return null
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
	 * Returns whether we are executing in the context on a console app.
	 *
	 * @return bool
	 */
	public function isConsole()
	{
		return true;
	}

	/**
	 * Override getComponent() so we can attach any pending events if the component is getting initialized as well as
	 * do some special logic around creating the `craft()->db` application component.
	 *
	 * @param string $id
	 * @param bool   $createIfNull
	 *
	 * @return mixed
	 */
	public function getComponent($id, $createIfNull = true)
	{
		$component = parent::getComponent($id, false);

		if (!$component && $createIfNull)
		{
			if ($id === 'db')
			{
				$dbConnection = $this->asa('AppBehavior')->createDbConnection();
				$this->setComponent('db', $dbConnection);
			}

			$component = parent::getComponent($id, true);
			$this->_attachEventListeners($id);
		}

		return $component;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @return ConsoleCommandRunner
	 */
	protected function createCommandRunner()
	{
		return new ConsoleCommandRunner();
	}

	// Private Methods
	// =========================================================================

	/**
	 * Attaches any pending event listeners to the newly-initialized component.
	 *
	 * @param string $componentId
	 *
	 * @return null
	 */
	private function _attachEventListeners($componentId)
	{
		if (isset($this->_pendingEvents[$componentId]))
		{
			$component = $this->getComponent($componentId, false);

			if ($component)
			{
				foreach ($this->_pendingEvents[$componentId] as $eventName => $handlers)
				{
					foreach ($handlers as $handler)
					{
						$component->$eventName = $handler;
					}
				}
			}
		}
	}
}
