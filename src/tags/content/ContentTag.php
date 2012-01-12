<?php

/**
 *
 */
class ContentTag extends Tag
{
	private $_siteId;

	/**
	 * @access public
	 */
	function __construct()
	{
		$this->_siteId = Blocks::app()->site->currentSiteByUrl->id;
	}

	/**
	 * @access public
	 *
	 * @param $method
	 * @param $args
	 *
	 * @return ContentSectionTag
	 */
	function __call($method, $args)
	{
		return $this->section($method);
	}

	/**
	 * @access public
	 *
	 * @param array $handles
	 *
	 * @return ContentSectionsTag
	 */
	public function sections($handles = array())
	{
		if (!$handles)
			$sections = Blocks::app()->content->getAllSectionsBySiteId($this->_siteId);
		else
			$sections = Blocks::app()->content->getSectionsBySiteIdHandles($this->_siteId, $handles);

		return new ContentSectionsTag($sections);
	}

	/**
	 * @access public
	 *
	 * @param $handle
	 *
	 * @return ContentSectionTag
	 */
	public function section($handle)
	{
		$section = Blocks::app()->content->getSectionBySiteIdHandle($this->_siteId, $handle);
		return new ContentSectionTag($section);
	}

	/**
	 * @access public
	 *
	 * @return ContentEntriesTag
	 */
	public function entries()
	{
		$entries = Blocks::app()->content->getAllEntriesBySiteId($this->_siteId);
		return new ContentEntriesTag($entries);
	}

	/**
	 * @access public
	 *
	 * @param $entryId
	 *
	 * @return ContentEntryTag
	 */
	public function entry($entryId)
	{
		$entry = Blocks::app()->content->getEntryById($entryId);
		return new ContentEntryTag($entry);
	}
}
