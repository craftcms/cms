<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Contracts;

use CraftCms\Cms\Component\Validation\Contracts\ValidatableComponentInterface;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Edition;
use InvalidArgumentException;

/**
 * PluginInterface defines the common interface to be implemented by plugin classes.
 */
interface PluginInterface
{
    /**
     * @var string The plugin's handle
     */
    public string $handle { get; set; }

    /**
     * @var string|null The plugin’s package name
     */
    public ?string $packageName { get; set; }

    /**
     * @var string|null The plugin’s display name
     */
    public ?string $name { get; set; }

    /**
     * @var string The plugin’s schema version number
     */
    public string $schemaVersion { get; set; }

    /**
     * @var string|null The plugin’s description
     */
    public ?string $description { get; set; }

    /**
     * @var string|null The plugin developer’s name
     */
    public ?string $developer { get; set; }

    /**
     * @var string|null The plugin developer’s website URL
     */
    public ?string $developerUrl { get; set; }

    /**
     * @var string|null The plugin developer’s support email
     */
    public ?string $developerEmail { get; set; }

    /**
     * @var string|null The plugin’s documentation URL
     */
    public ?string $documentationUrl { get; set; }

    /**
     * @var string|null The plugin’s changelog URL.
     *
     * The URL should begin with `https://` and point to a plain text Markdown-formatted changelog.
     * Version headers must follow the general format:
     *
     * ```
     * ## X.Y.Z - YYYY-MM-DD
     * ```
     *
     * with the following possible deviations:
     *
     * - other text can come before the version number, like the plugin’s name
     * - a 4th version number is allowed (e.g. `1.2.3.4`)
     * - pre-release versions are allowed (e.g. `1.0.0-alpha.1`)
     * - the version can start with `v` (e.g. `v1.2.3`)
     * - the version can be hyperlinked (e.g. `[1.2.3]`)
     * - dates can use dots as separators, rather than hyphens (e.g. `YYYY.MM.DD`)
     * - a `[CRITICAL]` flag can be appended after the date to indicate a critical release
     *
     * More notes:
     *
     * - Releases should be listed in descending order (newest on top). Craft will stop parsing the changelog as soon as it hits a version that is older than or equal to the installed version.
     * - Any content that does not follow a version header line will be ignored.
     * - For consistency and clarity, release notes should follow [keepachangelog.com](http://keepachangelog.com/), but it’s not enforced.
     * - Release notes can contain notes using the format `> {note} Some note`. `{warning}` and `{tip}` are also supported.
     */
    public ?string $changelogUrl { get; set; }

    /**
     * @var string|null The plugin’s download URL
     */
    public ?string $downloadUrl { get; set; }

    /**
     * @var string|null The translation category that this plugin’s translation messages should use. Defaults to the lowercased plugin handle.
     */
    public ?string $t9nCategory { get; set; }

    /**
     * @var bool Whether the plugin has a settings page in the control panel
     */
    public bool $hasCpSettings { get; set; }

    /**
     * @var bool Whether the plugin supports a read-only settings page in the control panel, which
     *           can be shown when admin changes are disallowed.
     */
    public bool $hasReadOnlyCpSettings { get; set; }

    /**
     * @var bool Whether the plugin has its own section in the control panel
     */
    public bool $hasCpSection { get; set; }

    /**
     * @var bool Whether the plugin is currently installed. (Will only be false when a plugin is currently being installed.)
     */
    public bool $isInstalled { get; set; }

    /**
     * @var string The current version of the plugin.
     */
    public string $version { get; set; }

    /**
     * @var string The minimum required version the plugin has to be so it can be updated.
     */
    public string $minVersionRequired { get; set; }

    /**
     * @var Edition The minimum required Craft CMS edition.
     */
    public Edition $minCmsEdition { get; set; }

    /**
     * @var string The active edition.
     */
    public string $edition { get; set; }

    /**
     * Returns supported plugin editions (lowest to highest).
     *
     * @return string[]
     */
    public static function editions(): array;

    public function install(): void;

    public function uninstall(): void;

    /**
     * @return Migrator The plugin’s migrator
     */
    public function getMigrator(): Migrator;

    /**
     * Returns the model that the plugin’s settings should be stored on, if the plugin has settings.
     *
     * @return ?ValidatableComponentInterface The model that the plugin’s settings should be stored on, if the plugin has settings
     *
     * @internal
     */
    public function getSettings(): ?ValidatableComponentInterface;

    /**
     * Sets the plugin settings
     *
     * @param  array  $settings  The plugin settings that should be set on the settings model
     */
    public function setSettings(array $settings): void;

    /**
     * Returns the settings page response.
     *
     * @return mixed The result that should be returned from [[\craft\controllers\PluginsController::actionEditPluginSettings()]]
     */
    public function getSettingsResponse(): mixed;

    /**
     * Returns a read-only version of the settings page response.
     *
     * This method is called when admin changes are disallowed, if [[$hasReadOnlyCpSettings]] is `true`.
     *
     * @return mixed The result that should be returned from [[\craft\controllers\PluginsController::actionEditPluginSettings()]]
     */
    public function getReadOnlySettingsResponse(): mixed;

    /**
     * Returns the control panel nav item definition for this plugin, if it has a section in the control panel.
     *
     * The returned array should contain the following keys:
     *
     * - `label` – The human-facing nav item label
     * - `url` – The URL the nav item should link to
     * - `id` – The HTML `id` attribute the nav item should have (optional)
     * - `icon` – The path to an SVG file that should be used as the nav item icon (optional)
     * - `fontIcon` – A character/ligature from Craft’s font icon set (optional)
     * - `badgeCount` – A number that should be displayed beside the nav item when unselected
     * - `subnav` – A sub-array of subnav items
     *
     * The subnav array should be associative, with identifiable keys set to sub-arrays with the following keys:
     *
     * - `label` – The human-facing subnav item label
     * - `url` – The URL the subnav item should link to
     *
     * For example:
     *
     * ```php
     * return [
     *     'label' => 'Commerce',
     *     'url' => 'commerce',
     *     'subnav' => [
     *         'orders' => ['label' => 'Orders', 'url' => 'commerce/orders',
     *         'discounts' => ['label' => 'Discounts', 'url' => 'commerce/discounts',
     *     ],
     * ];
     * ```
     *
     * Control panel templates can specify which subnav item is selected by defining a `selectedSubnavItem` variable.
     *
     * ```twig
     * {% set selectedSubnavItem = 'orders' %}
     * ```
     *
     * @see PluginTrait::$hasCpSection
     * @see Cp::nav()
     */
    public function getCpNavItem(): ?array;

    // Editions
    // -------------------------------------------------------------------------

    /**
     * Compares the active edition with the given edition.
     *
     * @param  string  $edition  The edition to compare the active edition against
     * @param  string  $operator  The comparison operator to use. `=` by default,
     *                            meaning the method will return `true` if the active edition is equal to
     *                            the passed-in edition.
     *
     * @throws InvalidArgumentException if `$edition` is an unsupported edition,
     *                                  or if `$operator` is an invalid operator.
     */
    public function is(string $edition, string $operator = '='): bool;

    // Events
    // -------------------------------------------------------------------------

    /**
     * Performs actions before the plugin’s settings are saved.
     *
     * @return bool Whether the plugin’s settings should be saved.
     */
    public function beforeSaveSettings(): bool;

    /**
     * Performs actions after the plugin’s settings are saved.
     */
    public function afterSaveSettings(): void;

    /**
     * Returns the root directory of the plugin.
     * It defaults to the directory containing the plugin class file.
     *
     * @return string the root directory of the plugin.
     */
    public function getBasePath(): string;

    /**
     * Creates and returns a new plugin instance based on a passed config
     */
    public static function create(array $config): self;

    /**
     * Get the instance of the plugin.
     */
    public static function getInstance(): self;
}
