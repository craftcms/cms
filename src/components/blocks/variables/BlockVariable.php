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
	 * @param mixed|null $value
	 * @return string
	 */
	public function input($value = null)
	{
		return $this->component->getInputHtml($value);
	}
}
