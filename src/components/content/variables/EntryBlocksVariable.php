<?php
namespace Blocks;

/**
 * Content functions
 */
class EntryBlocksVariable
{
	/**
	 * Returns all entry blocks.
	 *
	 * @return array
	 */
	public function entryBlocks()
	{
		$blocks = blx()->entryBlocks->getAllBlocks();
		return BlockVariable::populateVariables($blocks);
	}

	/**
	 * Returns all entry blocks by a given section ID.
	 *
	 * @param int $sectionId
	 * @return array
	 */
	public function entryBlocksBySectionId($sectionId)
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$blocks = blx()->sectionBlocks->getBlocksBySectionId($sectionId);
			return BlockVariable::populateVariables($blocks);
		}
	}

	/**
	 * Returns the total number of entry blocks by a given section ID.
	 *
	 * @param int $sectionId
	 * @return int
	 */
	public function totalEntryBlocksBySectionId($sectionId)
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			return blx()->sectionBlocks->getTotalBlocksBySectionId($sectionId);
		}
	}

	/**
	 * Gets an entry block by its ID.
	 *
	 * @param int $id
	 * @return BlockVariable|null
	 */
	public function getEntryBlockById($id)
	{
		$block = blx()->entryBlocks->getBlockById($id);
		if ($block)
		{
			return new BlockVariable($block);
		}
	}
}
