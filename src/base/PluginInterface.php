<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

use craft\db\MigrationManager;
use craft\web\twig\variables\Cp;

/**
 * PluginInterface defines the common interface to be implemented by plugin classes.
 *
 * A class implementing this interface should also use [[PluginTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
interface PluginInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the plugin’s handle (really just an alias of [[\yii\base\Module::id]]).
     *
     * @return string The plugin’s handle
     */
    public function getHandle(): string;

    /**
     * Installs the plugin.
     *
     * @return void|false Return `false` to indicate the installation failed.
     * All other return values mean the installation was successful.
     */
    public function install();

    /**
     * Uninstalls the plugin.
     *
     * @return void|false Return `false` to indicate the uninstallation failed.
     * All other return values mean the uninstallation was successful.
     */
    public function uninstall();

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
    public function getSettings();

    /**
     * Sets the plugin settings
     *
     * @param array $settings The plugin settings that should be set on the settings model
     */
    public function setSettings(array $settings);

    /**
     * Returns the settings page response.
     *
     * @return mixed The result that should be returned from [[\craft\controllers\PluginsController::actionEditPluginSettings()]]
     */
    public function getSettingsResponse();

    /**
     * Returns the CP nav item definition for this plugin’s CP section, if it has one.
     *
     * @return array|null
     * @see PluginTrait::$hasCpSection
     * @see Cp::nav()
     */
    public function getCpNavItem();
}
