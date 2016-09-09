<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

/**
 * PluginTrait implements the common methods and properties for plugin classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
trait PluginTrait
{
    // Properties
    // =========================================================================

    /**
     * @var string The plugin’s display name
     */
    public $name;

    /**
     * @var string The plugin’s version number
     */
    public $version;

    /**
     * @var string The plugin’s schema version number
     */
    public $schemaVersion;

    /**
     * @var string The plugin’s description
     */
    public $description;

    /**
     * @var string The plugin developer’s name
     */
    public $developer;

    /**
     * @var string The plugin developer’s website URL
     */
    public $developerUrl;

    /**
     * @var string The plugin’s documentation URL
     */
    public $documentationUrl;

    /**
     * @var string The plugin’s releases JSON feed
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
     */
    public $releaseFeedUrl;

    /**
     * @var string The language that the plugin’s messages were written in
     */
    public $sourceLanguage = 'en-US';

    /**
     * @var boolean Whether the plugin has settings
     */
    public $hasSettings = false;
}
