<?php
namespace Craft;

/**
 * Plugin base class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.plugins
 * @since     1.0
 */
abstract class BasePlugin extends BaseSavableComponentType implements IPlugin
{
	// Properties
	// =========================================================================

	/**
	 * @var bool
	 */
	public $isInstalled = false;

	/**
	 * @var bool
	 */
	public $isEnabled = false;

	/**
	 * The type of component, e.g. "Plugin", "Widget", "FieldType", etc. Defined by the component type's base class.
	 *
	 * @var string
	 */
	protected $componentType = 'Plugin';

	// Public Methods
	// =========================================================================

	/**
	 * A wrapper for writing to the log files for plugins that will ultimately call {@link Craft::log()}. This allows
	 * plugins to be able to write to their own log files at `craft/storage/runtime/logs/pluginHandle.log` using
	 * `PluginHandle::log()` syntax.
	 *
	 * @param string $msg   The message to be logged.
	 * @param string $level The level of the message (e.g. LogLevel::Trace', LogLevel::Info, LogLevel::Warning or
	 *                      LogLevel::Error).
	 * @param bool   $force Whether to force the message to be logged regardless of the level or category.
	 *
	 * @return mixed
	 */
	public static function log($msg, $level = LogLevel::Info, $force = false)
	{
		$plugin = get_called_class();

		// Chunk off any namespaces
		$parts = explode('\\', $plugin);
		if (count($parts) > 0)
		{
			$plugin = $parts[count($parts) - 1];
		}

		// Remove the trailing 'Plugin'.
		$plugin = str_replace('Plugin', '', $plugin);

		Craft::log($msg, $level, $force, 'plugin', StringHelper::toLowerCase($plugin));
	}

	/**
	 * @inheritDoc IPlugin::getSchemaVersion()
	 *
	 * @return string|null
	 */
	public function getSchemaVersion()
	{
		return null;
	}

	/**
	 * @inheritDoc IPlugin::getDescription()
	 *
	 * @return string|null
	 */
	public function getDescription()
	{
		return null;
	}

	/**
	 * @inheritDoc IPlugin::getDocumentationUrl()
	 *
	 * @return string|null
	 */
	public function getDocumentationUrl()
	{
		return null;
	}

	/**
	 * @inheritDoc IPlugin::getReleaseFeedUrl()
	 *
	 * @return string|null
	 */
	public function getReleaseFeedUrl()
	{
		return null;
	}

	/**
	 * @inheritDoc IPlugin::getSourceLanguage()
	 *
	 * @return string
	 */
	public function getSourceLanguage()
	{
		return craft()->sourceLanguage;
	}

	/**
	 * @inheritDoc IPlugin::hasSettings()
	 *
	 * @return bool Whether the plugin has settings
	 */
	public function hasSettings()
	{
		return $this->getSettingsUrl() || $this->getSettingsHtml();
	}

	/**
	 * @inheritDoc IPlugin::getSettingsUrl()
	 *
	 * @return string|null
	 */
	public function getSettingsUrl()
	{
		return null;
	}

	/**
	 * @inheritDoc IPlugin::hasCpSection()
	 *
	 * @return bool
	 */
	public function hasCpSection()
	{
		return false;
	}

	/**
	 * @inheritDoc IPlugin::createTables()
	 *
	 * @return void
	 */
	public function createTables()
	{
		$records = $this->getRecords('install');

		// Create all tables first
		foreach ($records as $record)
		{
			$record->createTable();
		}

		// Then add the foreign keys
		foreach ($records as $record)
		{
			$record->addForeignKeys();
		}
	}

	/**
	 * @inheritDoc IPlugin::dropTables()
	 *
	 * @return void
	 */
	public function dropTables()
	{
		$records = $this->getRecords();

		// Drop all foreign keys first
		foreach ($records as $record)
		{
			$record->dropForeignKeys();
		}

		// Then drop the tables
		foreach ($records as $record)
		{
			$record->dropTable();
		}
	}

	/**
	 * @inheritDoc IPlugin::getRecords()
	 *
	 * @param string|null $scenario
	 *
	 * @return BaseRecord[]
	 */
	public function getRecords($scenario = null)
	{
		$records = array();
		$classes = craft()->plugins->getPluginClasses($this, 'records', 'Record', false);

		foreach ($classes as $class)
		{
			if (craft()->components->validateClass($class))
			{
				$class = __NAMESPACE__.'\\'.$class;
				$records[] = new $class($scenario);
			}
		}

		return $records;
	}

	/**
	 * @inheritDoc IPlugin::onAfterInstall()
	 *
	 * @return void
	 */
	public function onAfterInstall()
	{
	}

	/**
	 * @inheritDoc IPlugin::onBeforeInstall()
	 *
	 * @return void
	 */
	public function onBeforeInstall()
	{
	}

	/**
	 * @inheritDoc IPlugin::onBeforeUninstall()
	 *
	 * @return void
	 */
	public function onBeforeUninstall()
	{
	}
}
