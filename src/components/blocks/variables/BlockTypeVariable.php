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
	 * @param int|null $entityId
	 * @return string
	 */
	public function input($handle, $value, $entityId = null)
	{
		return $this->component->getInputHtml($handle, $value, $entityId);
	}
}
