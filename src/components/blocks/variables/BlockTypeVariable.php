<?php
namespace Blocks;

/**
 * Block type template variable
 */
class BlockTypeVariable extends BaseComponentVariable
{
	/**
	 * Returns the block's input HTML.
	 *
	 * @param string $handle
	 * @param mixed $value
	 * @return string
	 */
	public function getInputHtml($handle, $value)
	{
		return $this->component->getInputHtml($handle, $value);
	}
}
