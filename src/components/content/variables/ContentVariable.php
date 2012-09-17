<?php
namespace Blocks;

/**
 * Content functions
 */
class ContentVariable
{
	/* BLOCKSPRO ONLY */
	// -------------------------------------------
	//  Sections
	// -------------------------------------------

	/**
	 * Gets sections.
	 *
	 * @param array|null $params
	 * @return array
	 */
	public function sections($params = array())
	{
		$params = new SectionParams($params);
		return blx()->content->getSections($params);
	}

	/**
	 * Gets a section.
	 *
	 * @param array|null $params
	 * @return SectionPackage|null
	 */
	public function section($params = array())
	{
		$params = new SectionParams($params);
		return blx()->content->getSection($params);
	}

	/**
	 * Gets the total number of sections.
	 *
	 * @param array|null $params
	 * @return int
	 */
	public function totalSections($params = array())
	{
		$params = new SectionParams($params);
		return blx()->content->getTotalSections($params);
	}

	/**
	 * Gets a section by its ID.
	 *
	 * @param int $id
	 * @return SectionPackage|null
	 */
	public function getSectionById($id)
	{
		return blx()->content->getSectionById($id);
	}

	/* end BLOCKSPRO ONLY */
	// -------------------------------------------
	//  Entry Blocks
	// -------------------------------------------

	/* BLOCKS ONLY */
	/**
	 * Returns all entry blocks.
	 *
	 * @return array
	 */
	public function entryBlocks()
	{
		return blx()->content->getEntryBlocks();
	}

	/* end BLOCKS ONLY */
	/* BLOCKSPRO ONLY */
	/**
	 * Returns all entry blocks by a given section ID.
	 *
	 * @param int $sectionId
	 * @return array
	 */
	public function entryBlocksBySectionId($sectionId)
	{
		return blx()->content->getEntryBlocksBySectionId($sectionId);
	}

	/**
	 * Returns the total number of entry blocks by a given section ID.
	 *
	 * @param int $sectionId
	 * @return int
	 */
	public function totalEntryBlocksBySectionId($sectionId)
	{
		return blx()->content->getTotalEntryBlocksBySectionId($sectionId);
	}

	/* end BLOCKSPRO ONLY */
	/**
	 * Gets an entry block by its ID.
	 *
	 * @param int $id
	 * @return BlockVariable
	 */
	public function getEntryBlockById($id)
	{
		return blx()->content->getEntryBlockById($id);
	}

	// -------------------------------------------
	//  Entries
	// -------------------------------------------

	/**
	 * Gets entries.
	 *
	 * @param array|null $params
	 * @return array
	 */
	public function entries($params = array())
	{
		$params = new EntryParams($params);
		return blx()->content->getEntries($params);
	}

	/**
	 * Gets an entry.
	 *
	 * @param array|null $params
	 * @return EntryPackage|null
	 */
	public function entry($params = array())
	{
		$params = new EntryParams($params);
		return blx()->content->getEntry($params);
	}

	/**
	 * Gets the total number of entries.
	 *
	 * @param array|null $params
	 * @return array
	 */
	public function totalEntries($params = array())
	{
		$params = new EntryParams($params);
		return blx()->content->getTotalEntries($params);
	}

	/**
	 * Gets an entry by its ID.
	 *
	 * @param int $id
	 * @return EntryPackage|null
	 */
	public function getEntryById($id)
	{
		return blx()->content->getEntryById($id);
	}
}
