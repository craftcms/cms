<?php
namespace Blocks;

/**
 *
 */
class ContentBlocksService extends BaseService
{
	private $_blockTypes;

	/**
	 * Returns all content blocks
	 * @return array
	 */
	public function getAll()
	{
		return ContentBlock::model()->findAll();
	}

	/**
	 * Returns a block by its ID
	 *
	 * @param $blockId
	 * @return ContentBlock
	 */
	public function getBlockById($blockId)
	{
		return ContentBlock::model()->findById($blockId);
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

			if (($files = @glob(Blocks::app()->path->blockTypesPath."*Block.php")) !== false)
			{
				foreach ($files as $file)
				{
					$className = pathinfo($file, PATHINFO_FILENAME);
					if ($className !== 'BaseBlock')
					{
						$className = 'Blocks\\'.$className;
						$this->_blockTypes[] = new $className;
					}
				}
			}
		}

		return $this->_blockTypes;
	}

}
