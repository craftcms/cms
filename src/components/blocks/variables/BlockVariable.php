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
	 * @param string     $handle
	 * @param mixed      $value
	 * @param array|null $errors
	 * @return string
	 */
	public function input($handle, $value, $errors = null)
	{
		return $this->component->getInputHtml($handle, $value, $errors);
	}
}
