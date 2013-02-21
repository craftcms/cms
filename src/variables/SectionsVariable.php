<?php
namespace Blocks;

/**
 *
 */
class SectionsVariable
{
	/**
	 * Returns all sections.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllSections($indexBy = null)
	{
		return blx()->sections->getAllSections($indexBy);
	}

	/**
	 * Returns all editable sections.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getEditableSections($indexBy = null)
	{
		return blx()->sections->getEditableSections($indexBy);
	}

	/**
	 * Returns a section by its ID.
	 *
	 * @param $sectionId
	 * @return SectionModel|null
	 */
	public function getSectionById($sectionId)
	{
		return blx()->sections->getSectionById($sectionId);
	}

	/**
	 * Returns a section by its handle.
	 *
	 * @param $handle
	 * @return SectionModel|null
	 */
	public function getSectionByHandle($handle)
	{
		return blx()->sections->getSectionByHandle($handle);
	}
}
