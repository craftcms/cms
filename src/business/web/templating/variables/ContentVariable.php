<?php
namespace Blocks;

/**
 * Content functions
 */
class ContentVariable
{
	/**
	 * Returns an entry by its ID
	 * @param int $entryId
	 */
	public function getEntryById($entryId)
	{
		return b()->content->getEntryById($entryId);
	}

	/**
	 * Returns a section by its ID
	 * @param int $sectionId
	 */
	public function getSectionById($sectionId)
	{
		return b()->content->getSectionById($sectionId);
	}

	/**
	 * Returns all sections
	 */
	public function sections()
	{
		return b()->content->getSections();
	}
}
