<?php
namespace Craft;

/**
 * Savable component type interface
 */
interface ISavableComponentType extends IComponentType
{
	/**
	 * @return BaseModel
	 */
	public function getSettings();

	/**
	 * @param array $values
	 */
	public function setSettings($values);

	/**
	 * @param array $settings
	 * @return array
	 */
	public function prepSettings($settings);

	/**
	 * @return string|null
	 */
	public function getSettingsHtml();
}
