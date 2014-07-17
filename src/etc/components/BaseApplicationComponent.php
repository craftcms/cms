<?php
namespace Craft;

/**
 * Class BaseApplicationComponent
 *
 * @package craft.app.etc.components
 */
class BaseApplicationComponent extends \CApplicationComponent
{
	// For consistency!
	/**
	 * @return bool
	 */
	public function isInitialized()
	{
		return $this->getIsInitialized();
	}
}
