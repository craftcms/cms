<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\console;

use Craft;
use craft\app\base\ApplicationTrait;
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

	use ApplicationTrait;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor.
	 *
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		Craft::$app = $this;
		parent::__construct($config);
	}

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
		//$this->get('log');

		// So we can try to translate Yii framework strings
		//$this->coreMessages->attachEventHandler('onMissingTranslation', ['Craft\LocalizationHelper', 'findMissingTranslation']);

		// Set our own custom runtime path.
		$this->setRuntimePath($this->path->getRuntimePath());

		// Attach our own custom Logger
		//Craft::setLogger(new Logger());

		// No need for these.
		$log = $this->getLog();
		$log->removeRoute('WebLogRoute');
		$log->removeRoute('ProfileLogRoute');

		// Set the edition components
		$this->_setEditionComponents();

		// Call parent::init() before the plugin console command logic so the command runner gets initialized
		parent::init();

		// Load the plugins
		$this->plugins->loadPlugins();

		// Validate some basics on the database configuration file.
		$this->validateDbConfigFile();

		foreach ($this->plugins->getPlugins() as $plugin)
		{
			$commandsPath = $this->path->getPluginsPath().StringHelper::toLowerCase($plugin->getClassHandle()).'/consolecommands/';

			if (IOHelper::folderExists($commandsPath))
			{
				$this->commandRunner->addCommands(rtrim($commandsPath, '/'));
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
	 * Override get() so we can do some special logic around creating the `$this->getDb()` application component.
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
