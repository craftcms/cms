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
	 * @return string
	 */
	public function input()
	{
		return $this->component->getInputHtml();
	}
}
