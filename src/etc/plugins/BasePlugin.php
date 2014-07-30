<?php
namespace Craft;

/**
 * Plugin base class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.plugins
 * @since     1.0
 */
abstract class BasePlugin extends BaseSavableComponentType implements IPlugin
{
	/**
	 * @var bool
	 */
	public $isInstalled = false;

	/**
	 * @var bool
	 */
	public $isEnabled = false;

	/**
	 * @var string The type of component this is.
	 */
	protected $componentType = 'Plugin';

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
	 * Returns the plugin’s version.
	 *
	 * @return string
	 */
	abstract public function getVersion();

	/**
	 * Returns the plugin developer's name.
	 *
	 * @return string
	 */
	abstract public function getDeveloper();

	/**
	 * Returns the plugin developer's URL.
	 *
	 * @return string
	 */
	abstract public function getDeveloperUrl();

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
	 * Drops any tables defined by the plugin's records.
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
	 * Perform any actions after the plugin has been installed.
	 *
	 * @return void
	 */
	public function onAfterInstall()
	{

	}

	/**
	 * Perform any actions before the plugin has been installed.
	 *
	 * @return void
	 */
	public function onBeforeInstall()
	{

	}

	/**
	 * Perform any actions before the plugin gets uninstalled.
	 *
	 * @return void
	 */
	public function onBeforeUninstall()
	{

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
	 * A wrapper for logging with plugins.
	 *
	 * @param string $msg
	 * @param string $level
	 *
	 * @param bool $force
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
}
