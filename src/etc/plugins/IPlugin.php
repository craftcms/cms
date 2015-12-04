<?php
namespace Craft;

/**
 * Interface IPlugin
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.plugins
 * @since     2.1
 */
interface IPlugin extends ISavableComponentType
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the plugin’s version number.
	 *
	 * @return string The plugin’s version number.
	 */
	public function getVersion();

	/**
	 * Returns the plugin’s schema version number.
	 *
	 * Changing the schema version number tells Craft that there are new migration files that need to be run.
	 *
	 * @return string|null The plugin’s version number, or null if it doesn’t need one
	 */
	public function getSchemaVersion();

	/**
	 * Returns the plugin’s description.
	 *
	 * @return string|null The plugin’s description.
	 */
	public function getDescription();

	/**
	 * Returns the plugin developer’s name.
	 *
	 * @return string The plugin developer’s name.
	 */
	public function getDeveloper();

	/**
	 * Returns the plugin developer’s URL.
	 *
	 * @return string The plugin developer’s URL.
	 */
	public function getDeveloperUrl();

	/**
	 * Returns the plugin documentation’s URL.
	 *
	 * @return string|null The plugin documentation’s URL.
	 */
	public function getDocumentationUrl();

	/**
	 * Returns the plugin’s releases JSON feed URL.
	 *
	 * If the plugin wants to have its updates included in the Updates page, it should provide a JSON feed in the
	 * following format:
	 *
	 * ```javascript
	 * [
	 *     {
	 *         "version": "0.9.0",
	 *         "downloadUrl": "https://download.craftcommerce.com/0.9/Commerce0.9.0.zip",
	 *         "date": "2015-12-01T10:00:00-08:00",
	 *         "notes": [
	 *             "# Big Stuff",
	 *             "[Added] It’s now possible to create new products right from Product Selector Modals (like the ones used by Products fields).",
	 *             "[Improved] Variants are now defined in a new Variant Matrix field, right on the main Edit Product pages.",
	 *             "# Bug Fixes",
	 *             "[Fixed] Fixed a Twig error that occurred if you manually went to /commerce/orders/new. You now receive a 404 error instead."
	 *         ]
	 *     },
	 *     {
	 *         "version": "0.9.1",
	 *         "downloadUrl": "https://download.craftcommerce.com/0.9/Commerce0.9.1.zip",
	 *         "date": "2015-12-01T11:00:00-08:00",
	 *         "notes": [
	 *             "[Fixed] Fixed a PHP error that occurred when creating a new produt when the current user’s username was ‘null’."
	 *         ]
	 *     }
	 * ]
	 * ```
	 *
	 * Notes:
	 *
	 * - The feed must be valid JSON.
	 * - The feed’s URL must begin with “https://” (so it is fetched over SSL).
	 * - Each release must contain `version`, `downloadUrl`, `date`, and `notes` attributes.
	 * - Each release’s `downloadUrl` must begin with “https://” (so it is downloaded over SSL).
	 * - Each release’s `date` must be an ISO-8601-formatted date, as defined by either
	 *   {@link http://php.net/manual/en/class.datetime.php#datetime.constants.atom DateTime::ATOM} or
	 *   {@link http://php.net/manual/en/class.datetime.php#datetime.constants.iso8601 DateTime::ISO8601} (with or without
	 *   the colon between the hours and minutes of the timezone offset).
	 * - `notes` can either be a string (with each release note separated by a newline character), or an array.
	 * - Release note lines that begin with `#` will be treated as headings.
	 * - Release note lines that begin with `[Added]`, `[Improved]`, or `[Fixed]` will be given `added`, `improved`,
	 *   and `fixed` classes within the Updates page.
	 * - Release note lines can contain Markdown code, but not HTML.
	 * - Releases can contain a `critical` attribute which can be set to `true` if the release is critical.
	 *
	 * @return string|null The plugin’s release feed URL
	 */
	public function getReleaseFeedUrl();

	/**
	 * Returns the locale ID that identifies what language the plugin was written in.
	 *
	 * @return string The plugin’s source language.
	 */
	public function getSourceLanguage();

	/**
	 * Returns whether the plugin has settings.
	 *
	 * @return bool Whether the plugin has settings
	 */
	public function hasSettings();

	/**
	 * Returns the URL to the plugin’s settings page in the CP.
	 *
	 * If your plugin requires a custom settings page, you can use this method to point to it.
	 *
	 * If this method returns anything, it will be run through {@link UrlHelper::getCpUrl()} before getting output,
	 * so a full URL is not necessary.
	 *
	 * If this method doesn’t return anything, a simple settings page will be provided for your plugin,
	 * filled with whatever {@link getSettingsHtml()} returns.
	 *
	 * @return string|null The URL to the plugin’s settings page, if it has a custom one.
	 */
	public function getSettingsUrl();

	/**
	 * Returns whether this plugin has its own section in the CP.
	 *
	 * @return bool Whether this plugin has its own section in the CP.
	 */
	public function hasCpSection();

	/**
	 * Creates any tables defined by the plugin’s records.
	 *
	 * @return void
	 */
	public function createTables();

	/**
	 * Drops any tables defined by the plugin’s records.
	 *
	 * @return void
	 */
	public function dropTables();

	/**
	 * Returns the record classes provided by this plugin.
	 *
	 * @param string|null $scenario The scenario to initialize the records with.
	 *
	 * @return BaseRecord[]
	 */
	public function getRecords($scenario = null);

	/**
	 * Performs any actions that should occur before the plugin is installed.
	 *
	 * @return void|false Return `false` to abort plugin installation
	 */
	public function onBeforeInstall();

	/**
	 * Performs any actions that should occur after the plugin is installed.
	 *
	 * @return void
	 */
	public function onAfterInstall();

	/**
	 * Performs any actions that should occur before the plugin is uninstalled.
	 *
	 * @return void
	 */
	public function onBeforeUninstall();
}
