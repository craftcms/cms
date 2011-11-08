<?php

class ContentTag extends Tag
{
	private $_siteId;

	function __call($method, $args)
	{
		return $this->section($method);
	}

	function __construct($siteId)
	{
		$this->_siteId = $siteId;
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

	public function pages()
	{
		$pages = Blocks::app()->content->getAllPagesBySiteId($this->_siteId);
		return new ContentPagesTag($pages);
	}

	public function page($pageId)
	{
		$page = Blocks::app()->content->getPageById($pageId);
		return new ContentPageTag($page);
	}
}
