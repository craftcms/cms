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
	/**
	 * @return BaseModel
	 */
	public function getSettings();

	/**
	 * @param array $values
	 *
	 * @return null
	 */
	public function setSettings($values);

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	public function prepSettings($settings);

	/**
	 * @return string|null
	 */
	public function getSettingsHtml();
}
