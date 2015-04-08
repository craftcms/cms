<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

/**
 * PluginInterface defines the common interface to be implemented by plugin classes.
 *
 * A class implementing this interface should also use [[PluginTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface PluginInterface
{
	// Static
	// =========================================================================

	/**
	 * Returns whether the plugin has its own section in the CP.
	 *
	 * @return bool Whether the plugin has its own section in the CP.
	 */
	public static function hasCpSection();

	// Public Methods
	// =========================================================================

	/**
	 * Returns the plugin’s handle.
	 *
	 * @return string The plugin’s handle
	 */
	public function getHandle();

	/**
	 * Installs the plugin.
	 *
	 * @return boolean|null Return `false` to indicate the installation failed.
	 * All other return values mean the installation was successful.
	 */
	public function install();

	/**
	 * Updates the plugin.
	 *
	 * @param string $fromVersion The previously installed version of the plugin.
	 * @return boolean|null Return `false` to indicate the update failed.
	 * All other return values mean the update was successful.
	 */
	public function update($fromVersion);

	/**
	 * Uninstalls the plugin.
	 *
	 * @return boolean|null Return `false` to indicate the uninstallation failed.
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
	 * Returns the settings page response.
	 *
	 * @return mixed The result that should be returned from [[PluginsController::actionRThe rendered settings page HTML
	 */
	public function getSettingsResponse();

	/**
	 * Returns the component definition that should be registered on the [[\craft\app\web\twig\variables\Craft]] instance for this plugin’s handle.
	 *
	 * @return mixed|null The component definition to be registered.
	 * It can be any of the formats supported by [[\yii\di\ServiceLocator::set()]].
	 */
	public function getVariableDefinition();
}
