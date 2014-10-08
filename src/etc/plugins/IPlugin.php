<?php
namespace Craft;

/**
 * Interface IPlugin
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.plugins
 * @since     2.1
 */
interface IPlugin extends ISavableComponentType
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the plugin’s version.
	 *
	 * @return string
	 */
	public function getVersion();

	/**
	 * Returns the plugin developer's name.
	 *
	 * @return string
	 */
	public function getDeveloper();

	/**
	 * Returns the plugin developer's URL.
	 *
	 * @return string
	 */
	public function getDeveloperUrl();

	/**
	 * Returns the plugin's source language
	 *
	 * @return string
	 */
	public function getSourceLanguage();

	/**
	 * Returns the URL to the plugin's settings in the CP.
	 *
	 * A full URL is not required -- you can simply return "pluginname/settings".
	 *
	 * If this is left blank, a simple settings page will be provided, filled with whatever getSettingsHtml() returns.
	 *
	 * @return string|null
	 */
	public function getSettingsUrl();

	/**
	 * Returns whether this plugin has its own section in the CP.
	 *
	 * @return bool
	 */
	public function hasCpSection();

	/**
	 * Creates any tables defined by the plugin's records.
	 *
	 * @return null
	 */
	public function createTables();

	/**
	 * Drops any tables defined by the plugin's records.
	 *
	 * @return null
	 */
	public function dropTables();

	/**
	 * Returns the record classes provided by this plugin.
	 *
	 * @param string|null $scenario The scenario to initialize the records with.
	 *
	 * @return array
	 */
	public function getRecords($scenario = null);

	/**
	 * Perform any actions after the plugin has been installed.
	 *
	 * @return null
	 */
	public function onAfterInstall();

	/**
	 * Perform any actions before the plugin has been installed.
	 *
	 * @return null
	 */
	public function onBeforeInstall();

	/**
	 * Perform any actions before the plugin gets uninstalled.
	 *
	 * @return null
	 */
	public function onBeforeUninstall();
}
