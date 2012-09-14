<?php
namespace Blocks;

/**
 * Block template variable
 */
class BlockVariable extends ComponentVariable
{
	/**
	 * Returns the block's input HTML.
	 *
	 * @param array|null $settings
	 * @param mixed      $package
	 * @param string     $handle
	 * @return string
	 */
	public function input($settings, $package, $handle)
	{
		$this->component->getSettings()->setAttributes($settings);
		return $this->component->getInputHtml($package, $handle);
	}
}
