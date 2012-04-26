<?php
namespace Blocks;

/**
 *
 */
class BlocksService extends Component
{
	private $_blockTypes;

	/**
	 * Returns all content blocks
	 * @return array
	 */
	public function getBlocks()
	{
		$blocks = b()->db->createCommand()->from('blocks')->queryAll();
		return Block::model()->populateSubclassRecords($blocks);
	}

	/**
	 * Returns a new block by the blocktype
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
	 * Returns a block by its ID
	 * @param int $blockId
	 * @return Block
	 */
	public function getBlockById($blockId)
	{
		$block = b()->db->createCommand()
			->where('id = :id', array(':id' => $blockId))
			->from('blocks')
			->queryRow();

		if ($block)
			return Block::model()->populateSubclassRecord($block);

		return null;
	}

	/**
	 * Returns all block types
	 * @return array
	 */
	public function getBlockTypes()
	{
		if (!isset($this->_blockTypes))
		{
			$this->_blockTypes = array();

			if (($files = @glob(b()->path->blockTypesPath."*Block.php")) !== false)
			{
				foreach ($files as $file)
				{
					$className = pathinfo($file, PATHINFO_FILENAME);
					if (substr($className, 0, 4) !== 'Base')
					{
						$className = __NAMESPACE__.'\\'.$className;
						$this->_blockTypes[] = new $className;
					}
				}
			}
		}

		return $this->_blockTypes;
	}
}
