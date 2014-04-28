<?php
namespace Craft;

/**
 * Plugin interface
 */
interface IPlugin extends ISavableComponentType
{
	/**
	 * @return string|null
	 */
	public function getSettingsUrl();
}
