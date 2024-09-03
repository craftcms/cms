<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\db\MigrationManager;
use craft\web\twig\variables\Cp;
use yii\base\Module;

/**
 * PluginInterface defines the common interface to be implemented by plugin classes.
 * A class implementing this interface should also use [[PluginTrait]].
 *
 * @mixin PluginTrait
 * @mixin Module
 * @phpstan-require-extends Module
 * @property string $handle The plugin’s handle (alias of [[id]])
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
interface PluginInterface
{
    /**
     * Returns the base config that the plugin should be instantiated with.
     *
     * It is recommended that plugins define their internal components from here:
     *
     * ```php
     * public static function config(): array
     * {
     *     return [
     *         'components' => [
     *             'myComponent' => ['class' => MyComponent::class],
     *             // ...
     *         ],
     *     ];
     * }
     * ```
     *
     * Doing that enables projects to customize the components as needed, by
     * overriding `\craft\services\Plugins::$pluginConfigs` in `config/app.php`:
     *
     * ```php
     * return [
     *     'components' => [
     *         'plugins' => [
     *             'pluginConfigs' => [
     *                 'my-plugin' => [
     *                     'components' => [
     *                         'myComponent' => [
     *                             'myProperty' => 'foo',
     *                             // ...
     *                         ],
     *                     ],
     *                 ],
     *             ],
     *         ],
     *     ],
     * ];
     * ```
     *
     * The resulting config will be passed to `\Craft::createObject()` to instantiate the plugin.
     *
     * @return array
     * @since 4.0.0
     */
    public static function config(): array;

    /**
     * Returns supported plugin editions (lowest to highest).
     *
     * @return string[]
     * @since 3.1.0
     */
    public static function editions(): array;

    /**
     * Returns the plugin’s handle (really just an alias of [[\yii\base\Module::id]]).
     *
     * @return string The plugin’s handle
     */
    public function getHandle(): string;

    /**
     * Installs the plugin.
     *
     */
    public function install(): void;

    /**
     * Uninstalls the plugin.
     *
     */
    public function uninstall(): void;

    /**
     * Returns the plugin’s migration manager
     *
     * @return MigrationManager The plugin’s migration manager
     */
    public function getMigrator(): MigrationManager;

    /**
     * Returns the model that the plugin’s settings should be stored on, if the plugin has settings.
     *
     * @return Model|null The model that the plugin’s settings should be stored on, if the plugin has settings
     */
    public function getSettings(): ?Model;

    /**
     * Sets the plugin settings
     *
     * @param array $settings The plugin settings that should be set on the settings model
     */
    public function setSettings(array $settings): void;

    /**
     * Returns the settings page response.
     *
     * @return mixed The result that should be returned from [[\craft\controllers\PluginsController::actionEditPluginSettings()]]
     */
    public function getSettingsResponse(): mixed;

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
     * @return array|null
     * @see PluginTrait::$hasCpSection
     * @see Cp::nav()
     */
    public function getCpNavItem(): ?array;

    // Events
    // -------------------------------------------------------------------------

    /**
     * Performs actions before the plugin’s settings are saved.
     *
     * @return bool Whether the plugin’s settings should be saved.
     * @since 3.0.16
     */
    public function beforeSaveSettings(): bool;

    /**
     * Performs actions after the plugin’s settings are saved.
     *
     * @since 3.0.16
     */
    public function afterSaveSettings(): void;
}
