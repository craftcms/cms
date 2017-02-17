<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

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
     * Installs the plugin.
     *
     * @return void|false Return `false` to indicate the installation failed.
     * All other return values mean the installation was successful.
     */
    public function install();

    /**
     * Updates the plugin.
     *
     * @param string $fromVersion The previously installed version of the plugin.
     *
     * @return void|false Return `false` to indicate the update failed.
     * All other return values mean the update was successful.
     */
    public function update(string $fromVersion);

    /**
     * Uninstalls the plugin.
     *
     * @return void|false Return `false` to indicate the uninstallation failed.
     * All other return values mean the uninstallation was successful.
     */
    public function uninstall();

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
     * @return mixed The result that should be returned from [[PluginsController::actionEditPluginSettings()]]
     */
    public function getSettingsResponse();

    /**
     * Returns the component definition that should be registered on the [[\craft\web\twig\variables\CraftVariable]] instance for this plugin’s handle.
     *
     * @return mixed|null The component definition to be registered.
     * It can be any of the formats supported by [[\yii\di\ServiceLocator::set()]].
     */
    public function defineTemplateComponent();
}
