<?php
namespace Blocks;

/**
 * Content functions
 */
class ContentVariable
{
	/**
	 * Returns an entry by its ID.
	 * @param int $entryId
	 * @return Entry
	 */
	public function getEntryById($entryId)
	{
		return blx()->content->getEntryById($entryId);
	}

	/**
	 * Returns a section by its ID.
	 * @param int $sectionId
	 * @return Section
	 */
	public function getSectionById($sectionId)
	{
		return blx()->content->getSectionById($sectionId);
	}

	/**
	 * Returns all sections.
	 * @return array
	 */
	public function sections()
	{
		return blx()->content->getSections();
	}
}
