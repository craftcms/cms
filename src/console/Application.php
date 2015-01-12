<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\console;

use Craft;
use craft\app\etc\console\ConsoleCommandRunner;
use craft\app\helpers\IOHelper;
use craft\app\helpers\StringHelper;
use craft\app\logging\Logger;

/**
 * Craft Console Application class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Application extends \yii\console\Application
{
	// Traits
	// =========================================================================

	use \craft\app\base\ApplicationTrait;

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	public $componentAliases;

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

		// Initialize Cache and LogRouter right away (order is important)
		$this->get('cache');
		$this->get('log');

		// So we can try to translate Yii framework strings
		$this->coreMessages->attachEventHandler('onMissingTranslation', ['Craft\LocalizationHelper', 'findMissingTranslation']);

		// Set our own custom runtime path.
		$this->setRuntimePath(Craft::$app->path->getRuntimePath());

		// Attach our own custom Logger
		Craft::setLogger(new Logger());

		// No need for these.
		Craft::$app->log->removeRoute('WebLogRoute');
		Craft::$app->log->removeRoute('ProfileLogRoute');

		// Set the edition components
		$this->_setEditionComponents();

		// Call parent::init() before the plugin console command logic so the command runner gets initialized
		parent::init();

		// Load the plugins
		Craft::$app->plugins->loadPlugins();

		// Validate some basics on the database configuration file.
		Craft::$app->validateDbConfigFile();

		foreach (Craft::$app->plugins->getPlugins() as $plugin)
		{
			$commandsPath = Craft::$app->path->getPluginsPath().StringHelper::toLowerCase($plugin->getClassHandle()).'/consolecommands/';

			if (IOHelper::folderExists($commandsPath))
			{
				Craft::$app->commandRunner->addCommands(rtrim($commandsPath, '/'));
			}
		}
	}

	/**
	 * Returns the target application language.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->_getLanguage();
	}

	/**
	 * Sets the target application language.
	 *
	 * @param string $language
	 *
	 * @return null
	 */
	public function setLanguage($language)
	{
		$this->_setLanguage($language);
	}

	/**
	 * Attaches an event handler, or remembers it for later if the component has not been initialized yet.
	 *
	 * The event should be identified in a `serviceHandle.eventName` format. For example, if you want to add an event
	 * handler for [[\craft\app\services\Entries::onSaveEntry()]], you would do this:
	 *
	 * ```php
	 * Craft::$app->on('entries.saveEntry', function(Event $event) {
	 *     // ...
	 * });
	 * ```
	 *
	 * Note that the actual event name (`saveEntry`) does not need to include the “`on`”.
	 *
	 * By default, event handlers will not get attached if Craft is current in the middle of updating itself or a
	 * plugin. If you want the event to fire even in that condition, pass `true` to the $evenDuringUpdates argument.
	 *
	 * @param string $event             The event to listen for.
	 * @param mixed  $handler           The event handler.
	 * @param bool   $evenDuringUpdates Whether the event handler should be attached when Craft’s updater is running.
	 *                                  Default is `false`.
	 *
	 * @return null
	 */
	public function on($event, $handler, $evenDuringUpdates = false)
	{
		if (
			!$evenDuringUpdates &&
			($this->getCommandRunner()->getCommand() instanceof \MigrateCommand)
		)
		{
			return;
		}

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
	 * Override get() so we can do some special logic around creating the `Craft::$app->db` application component.
	 *
	 * @param string $id
	 * @param boolean $throwException
	 * @return object|null
	 */
	public function get($id, $throwException = true)
	{
		// Are they requesting the DbConnection, and is this the first time it has been requested?
		if ($id === 'db' && !$this->has($id, true))
		{
			$dbConnection = $this->_createDbConnection();
			$this->set('db', $dbConnection);
		}

		return parent::get($id, $throwException);
	}

	/**
	 * Returns the system time zone.
	 *
	 * Note that this method cannot be in [[\craft\app\base\ApplicationTrait]], because Yii will check
	 * [[\yii\base\Application::getTimeZone()]] instead.
	 *
	 * @return string
	 */
	public function getTimeZone()
	{
		return $this->_getTimeZone();
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
}
