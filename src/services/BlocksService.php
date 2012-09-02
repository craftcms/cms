<?php
namespace Blocks;

/**
 *
 */
class BlocksService extends \CApplicationComponent
{
	private $_blockTypes;

	/**
	 * Returns a new block by the blocktype
	 *
	 * @param string $class The blocktype class, sans "Block" suffix
	 * @return mixed The block instance
	 */
	public function getBlockByClass($class)
	{
		$class = __NAMESPACE__.'\\'.$class.'Block';
		$block = new $class;
		return $block;
	}

	/**
	 * Returns all block types
	 *
	 * @return array
	 */
	public function getBlockTypes()
	{
		if (!isset($this->_blockTypes))
			$this->_blockTypes = ComponentHelper::getComponents('blocktypes', 'Block');

		return $this->_blockTypes;
	}
}
