<?php

class ContentTag extends Tag
{
	private $_siteId;

	function __call($method, $args)
	{
		//$args[0]['handle'] = $method;
		return $this->section($method);
	}

	// TODO: figure out what to do if repo query returns null... exception?

	function __construct($siteId)
	{
		$this->_siteId = $siteId;
	}

	public function sections($handles = array())
	{
		if (!$handles)
			$sections = Blocks::app()->contentRepo->getAllSectionsBySiteId($this->_siteId);
		else
			$sections = Blocks::app()->contentRepo->getSectionsByHandles($handles);

		return new ContentSectionsTag($sections);
	}

	public function section($handle)
	{
		$section = Blocks::app()->contentRepo->getSectionByHandle($handle);
		return new ContentSectionTag($section);
	}

	public function pages()
	{
		$pages = Blocks::app()->contentRepo->getAllPagesBySiteId($this->_siteId);
		return new ContentPagesTag($pages);
	}
}
