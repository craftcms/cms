<?php
namespace Craft;

/**
 * Plugin base class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
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
	 * @param        $msg   The message to be logged.
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
	 * Returns the plugin's source language
	 *
	 * @return string
	 */
	public function getSourceLanguage()
	{
		return craft()->sourceLanguage;
	}

	/**
	 * Returns the URL to the plugin's settings in the CP.
	 *
	 * A full URL is not required -- you can simply return "pluginname/settings".
	 *
	 * If this is left blank, a simple settings page will be provided, filled with whatever getSettingsHtml() returns.
	 *
	 * @return string|null
	 */
	public function getSettingsUrl()
	{
	}

	/**
	 * Returns whether this plugin has its own section in the CP.
	 *
	 * @return bool
	 */
	public function hasCpSection()
	{
		return false;
	}

	/**
	 * Creates any tables defined by the plugin's records.
	 *
	 * @return null
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
	 * Drops any tables defined by the plugin's records.
	 *
	 * @return null
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
	 * Returns the record classes provided by this plugin.
	 *
	 * @param string|null $scenario The scenario to initialize the records with.
	 *
	 * @return array
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
	 * Perform any actions after the plugin has been installed.
	 *
	 * @return null
	 */
	public function onAfterInstall()
	{
	}

	/**
	 * Perform any actions before the plugin has been installed.
	 *
	 * @return null
	 */
	public function onBeforeInstall()
	{
	}

	/**
	 * Perform any actions before the plugin gets uninstalled.
	 *
	 * @return null
	 */
	public function onBeforeUninstall()
	{
	}
}
