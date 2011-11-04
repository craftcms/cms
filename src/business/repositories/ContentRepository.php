<?php

class ContentRepository extends CApplicationComponent implements IContentRepository
{
	/*
	 * Pages
	 */
	public function getPageById($pageId)
	{
		$page = ContentPages::model()->findByAttributes(array(
			'id' => $pageId,
		));

		return $page;
	}

	public function getPagesBySectionId($sectionId)
	{
		$pages = ContentPages::model()->findAllByAttributes(array(
			'section_id' => $sectionId,
		));

		return $pages;
	}

	public function getPageTitleByLanguageCode($pageId, $languageCode)
	{
		$pageTitle = ContentPageTitles::model()->findByAttributes(
			array(
				'page_id' => $pageId,
				'language_code' => $languageCode),
			array(
				'select' => 'title'));

		return $pageTitle;
	}

	public function getAllPagesBySiteId($siteId)
	{
		$prefix = Blocks::app()->configRepo->getDatabaseTablePrefix().'_';
		$pages = Blocks::app()->db->createCommand()
			->select('cp.*')
			->from($prefix.'contentsections cs')
			->join($prefix.'contentpages cp', 'cs.id = cp.section_id')
			->where('cs.site_id=:siteId', array(':siteId' => $siteId))
			->queryAll();

		return $pages;
	}

	public function getBlocksByPageId($pageId)
	{
		
	}

	/*
	 * Sections
	 */
	public function getSectionById($sectionId)
	{
		$section = ContentSections::model()->findByAttributes(array(
			'id' => $sectionId,
		));

		return $section;
	}

	public function getSectionByHandle($handle)
	{
		$section = ContentSections::model()->findByAttributes(array(
			'handle' => $handle,
		));

		return $section;
	}

	public function getSectionsByHandles($handles)
	{
		$sections = ContentSections::model()->findAllByAttributes(array(
			'handle' => $handles,
		));

		return $sections;
	}

	public function getAllSectionsBySiteId($siteId)
	{
		$sections = ContentSections::model()->findAllByAttributes(array(
			'site_id' => $siteId,
		));

		return $sections;
	}
}
