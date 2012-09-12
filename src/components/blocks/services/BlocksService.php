<?php
namespace Blocks;

/**
 *
 */
class BlocksService extends BaseApplicationComponent
{
	/**
	 * Returns all installed blocks.
	 *
	 * @return array
	 */
	public function getAllBlocks()
	{
		return blx()->components->getComponentsByType('block');
	}

	/**
	 * Gets a block by its class
	 *
	 * @param string $class
	 * @return mixed
	 */
	public function getBlockByClass($class)
	{
		return blx()->components->getComponentByTypeAndClass('block', $class);
	}

	/**
	 * Populates a block with a given record.
	 *
	 * @param BaseBlockRecord $record
	 * @return BaseBlock
	 */
	public function populateBlock(BaseBlockRecord $record)
	{
		return blx()->components->populateComponent('block', $record);
	}

	/**
	 * Creates an array of blocks based on an array of block records.
	 *
	 * @param array $records
	 * @return array
	 */
	public function populateBlocks($records)
	{
		return blx()->components->populateComponents('block', $records);
	}
}
