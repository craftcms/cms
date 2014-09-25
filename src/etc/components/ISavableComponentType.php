<?php
namespace Craft;

/**
 * Savable component type interface.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.components
 * @since     1.0
 */
interface ISavableComponentType extends IComponentType
{
	// Public Methods
	// =========================================================================

	/**
	 * Gets the settings.
	 *
	 * @return BaseModel
	 */
	public function getSettings();

	/**
	 * Sets the setting values.
	 *
	 * @param array|BaseModel $values
	 *
	 * @return null
	 */
	public function setSettings($values);

	/**
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function prepSettings($settings);

	/**
	 * Returns the component's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml();
}
