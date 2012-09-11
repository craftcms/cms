<?php
namespace Blocks;

/**
 *
 */
class ApplicationComponent extends \CApplicationComponent
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
