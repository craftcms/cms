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
		return blx()->entries->getAllBlocks();
	}

	/**
	 * Returns all entry blocks by a given section ID.
	 *
	 * @param int $sectionId
	 * @return array
	 */
	public function entryBlocksBySectionId($sectionId)
	{
		return blx()->sections->getBlocksBySectionId($sectionId);
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
			return blx()->sections->getTotalBlocksBySectionId($sectionId);
		}
	}

	/**
	 * Gets an entry block by its ID.
	 *
	 * @param int $id
	 * @return EntryBlockModel|null
	 */
	public function getEntryBlockById($id)
	{
		return blx()->entries->getBlockById($id);
	}
}
