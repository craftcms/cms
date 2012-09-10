<?php
namespace Blocks;

/**
 * Content functions
 */
class ContentVariable
{
	/* BLOCKS ONLY */

	/**
	 * Returns all entry blocks.
	 *
	 * @return array
	 */
	public function entryBlocks()
	{
		$blocks = blx()->content->getEntryBlocks();
		return VariableHelper::populateComponentVariables($blocks, 'BlockVariable');
	}

	/* end BLOCKS ONLY */
	/* BLOCKSPRO ONLY */

	/**
	 * Gets sections.
	 *
	 * @param array $params
	 * @return array
	 */
	public function sections($params = array())
	{
		$records = blx()->content->getSections($params);
		return VariableHelper::populateModelVariables($records, 'SectionVariable');
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
	 * @return Section
	 */
	public function getSectionById($id)
	{
		$record = blx()->content->getSectionById($id);
		if ($record)
			return new SectionVariable($record);
	}

	/* end BLOCKSPRO ONLY */

	/**
	 * Gets an entry block by its ID.
	 *
	 * @param int $id
	 * @return EntryBlock
	 */
	public function getEntryBlockById($id)
	{
		$block = blx()->content->getEntryBlockById($id);
		if ($block)
			return new BlockVariable($block);
	}
}
