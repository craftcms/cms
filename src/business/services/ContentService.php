<?php

class ContentService extends CApplicationComponent implements IContentService
{
	/*
	 * Entries
	 */
	public function getEntryById($entryId)
	{
		$entry = Entries::model()->findByAttributes(array(
			'id' => $entryId,
		));

		return $entry;
	}

	public function getEntriesBySectionId($sectionId)
	{
		$entries = Entries::model()->findAllByAttributes(array(
			'section_id' => $sectionId,
		));

		return $entries;
	}

	public function getEntryTitleByLanguageCode($entryId, $languageCode)
	{
		$entryTitle = EntryTitles::model()->findByAttributes(
			array(
				'entry_id' => $entryId,
				'language_code' => $languageCode),
			array(
				'select' => 'title'));

		return $entryTitle;
	}

	public function getAllEntriesBySiteId($siteId)
	{
		$entries = Blocks::app()->db->createCommand()
			->select('e.*')
			->from('{{sections}} s')
			->join('{{entries}} e', 's.id = e.section_id')
			->where('s.site_id=:siteId', array(':siteId' => $siteId))
			->queryAll();

		return $entries;
	}

	public function doesEntryHaveSubEntries($entryId)
	{
		$exists = Entries::model()->exists(
			'parent_id=:parentId',
			array(':parentId' => $entryId)
		);

		return $exists;
	}

	public function getEntryVersionsByEntryId($entryId)
	{
		$versions = EntryVersions::model()->findAllByAttributes(array(
			'entry_id' => $entryId,
		));

		return $versions;
	}

	public function getEntryVersionById($versionId)
	{
		$version = EntryVersions::model()->findByAttributes(array(
			'id' => $versionId,
		));

		return $version;
	}

	/*
	 * Sections
	 */
	public function getSectionById($sectionId)
	{
		$section = Sections::model()->findByAttributes(array(
			'id' => $sectionId,
		));

		return $section;
	}

	public function doesSectionHaveSubSections($sectionId)
	{
		$exists = Sections::model()->exists(
			'parent_id=:parentId',
			array(':parentId' => $sectionId)
		);

		return $exists;
	}

	public function getSectionBySiteIdHandle($siteId, $handle)
	{
		$section = Sections::model()->findByAttributes(array(
			'handle' => $handle,
			'site_id' => $siteId,
		));

		return $section;
	}

	public function getSectionsBySiteIdHandles($siteId, $handles)
	{
		$sections = Sections::model()->findAllByAttributes(array(
			'handle' => $handles,
			'site_id' => $siteId,
		));

		return $sections;
	}

	public function getAllSectionsBySiteId($siteId)
	{
		$sections = Sections::model()->findAllByAttributes(array(
			'site_id' => $siteId,
		));

		return $sections;
	}

	/*
	 * Blocks
	 */
	public function getBlocksBySectionId($sectionId)
	{
		$sections = EntryBlocks::model()->findAllByAttributes(array(
			'section_id' => $sectionId,
		));

		return $sections;
	}

	public function getBlocksByEntryId($entryId)
	{
		$blocks = Blocks::app()->db->createCommand()
			->select('eb.*')
			->from('{{entryblocks}} eb')
			->join('{{sections}} s', 's.id = eb.section_id')
			->join('{{entries}} e', 's.id = e.section_id')
			->where('e.id=:entryId', array(':entryId' => $entryId))
			->queryAll();

		return $blocks;
	}

	public function getBlockByEntryIdHandle($entryId, $handle)
	{
		$blocks = Blocks::app()->db->createCommand()
			->select('eb.*')
			->from('{{entryblocks}} eb')
			->join('{{sections}} s', 's.id = eb.section_id')
			->join('{{entries}} e', 's.id = e.section_id')
			->where('e.id=:entryId AND eb.handle=:handle', array(':entryId' => $entryId, ':handle' => $handle))
			->queryAll();

		return $blocks;
	}
}
