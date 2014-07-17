<?php
namespace Craft;

/**
 * Interface IPlugin
 *
 * @package craft.app.etc.plugins
 */
interface IPlugin extends ISavableComponentType
{
	/**
	 * @return string|null
	 */
	public function getSettingsUrl();
}
