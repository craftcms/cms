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
	 * @param string $handle
	 * @param mixed  $value
	 * @return string
	 */
	public function input($handle, $value)
	{
		return $this->component->getInputHtml($handle, $value);
	}
}
