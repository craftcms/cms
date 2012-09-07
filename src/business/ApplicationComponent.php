<?php
namespace Blocks;

/**
 *
 */
class ApplicationComponent extends \CApplicationComponent
{
	// For consistency!

	public function isInitialized()
	{
		return $this->getIsInitialized();
	}
}
