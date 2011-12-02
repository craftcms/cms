<?php

class ContentTag extends Tag
{
	private $_siteId;

	function __construct()
	{
		$this->_siteId = Blocks::app()->request->getSiteInfo()->id;
	}

	function __call($method, $args)
	{
		return $this->section($method);
	}

	public function sections($handles = array())
	{
		if (!$handles)
			$sections = Blocks::app()->content->getAllSectionsBySiteId($this->_siteId);
		else
			$sections = Blocks::app()->content->getSectionsBySiteIdHandles($this->_siteId, $handles);

		return new ContentSectionsTag($sections);
	}

	public function section($handle)
	{
		$section = Blocks::app()->content->getSectionBySiteIdHandle($this->_siteId, $handle);
		return new ContentSectionTag($section);
	}

	public function entries()
	{
		$entries = Blocks::app()->content->getAllEntriesBySiteId($this->_siteId);
		return new ContentEntriesTag($entries);
	}

	public function entry($entryId)
	{
		$entry = Blocks::app()->content->getEntryById($entryId);
		return new ContentEntryTag($entry);
	}
}
