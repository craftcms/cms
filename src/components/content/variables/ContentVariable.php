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
	 * @param array $params
	 * @return array
	 */
	public function sections($params = array())
	{
		$records = blx()->content->getSections($params);
		return VariableHelper::populateVariables($records, 'SectionVariable');
	}

	/**
	 * Gets the total number of sections.
	 *
	 * @param array $params
	 * @return int
	 */
	public function totalSections($params = array())
	{
		return blx()->content->getTotalSections($params);
	}

	/**
	 * Gets a section by its ID.
	 *
	 * @param int $id
	 * @return SectionVariable
	 */
	public function getSectionById($id)
	{
		$record = blx()->content->getSectionById($id);
		if ($record)
			return new SectionVariable($record);
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
		$blocks = blx()->content->getEntryBlocks();
		return VariableHelper::populateVariables($blocks, 'BlockVariable');
	}
	/* end BLOCKS ONLY */

	/**
	 * Gets an entry block by its ID.
	 *
	 * @param int $id
	 * @return BlockVariable
	 */
	public function getEntryBlockById($id)
	{
		$block = blx()->content->getEntryBlockById($id);
		if ($block)
			return new BlockVariable($block);
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
