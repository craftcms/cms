<?php

class ContentTag extends Tag
{
	private $_siteId;

	function __call($method, $args)
	{
		//$args[0]['handle'] = $method;
		return $this->section($method);
	}

	// TODO: figure out what to do if service query returns null... exception?

	function __construct($siteId)
	{
		$this->_siteId = $siteId;
	}

	public function sections($handles = array())
	{
		if (!$handles)
			$sections = Blocks::app()->content->getAllSectionsBySiteId($this->_siteId);
		else
			$sections = Blocks::app()->content->getSectionsByHandles($handles);

		return new ContentSectionsTag($sections);
	}

	public function section($handle)
	{
		$section = Blocks::app()->content->getSectionByHandle($handle);
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
