<?php
namespace Blocks;

/**
 * Content functions
 */
class EntriesVariable
{
	// -------------------------------------------
	//  Entry Blocks
	// -------------------------------------------

	/**
	 * Returns all entry blocks.
	 *
	 * @return array
	 */
	public function getAllBlocks()
	{
		return blx()->entries->getAllBlocks();
	}

	/**
	 * Returns all entry blocks by a given section ID.
	 *
	 * @param int $sectionId
	 * @return array
	 */
	public function getBlocksBySectionId($sectionId)
	{
		return blx()->sections->getBlocksBySectionId($sectionId);
	}

	/**
	 * Returns the total number of entry blocks by a given section ID.
	 *
	 * @param int $sectionId
	 * @return int
	 */
	public function getTotalBlocksBySectionId($sectionId)
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

	// -------------------------------------------
	//  Entries
	// -------------------------------------------

	/**
	 * Gets the total number of entries.
	 *
	 * @param array|null $params
	 * @return array
	 */
	public function getTotalEntries($params = array())
	{
		$params = new EntryParams($params);
		return blx()->entries->getTotalEntries($params);
	}

	/**
	 * Finds entries.
	 *
	 * @param array|null $params
	 * @return array
	 */
	public function findEntries($params = array())
	{
		$params = new EntryParams($params);
		return blx()->entries->findEntries($params);
	}

	/**
	 * Finds an entry.
	 *
	 * @param array|null $params
	 * @return EntryModel|null
	 */
	public function findEntry($params = array())
	{
		$params = new EntryParams($params);
		return blx()->entries->findEntry($params);
	}
}
