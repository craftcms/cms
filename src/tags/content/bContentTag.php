<?php

/**
 *
 */
class bContentTag extends bTag
{
	private $_siteId;

	/**
	 */
	function __construct()
	{
		$this->_siteId = Blocks::app()->site->currentSiteByUrl->id;
	}

	/**
	 * @param $method
	 * @param $args
	 * @return bContentSectionTag
	 */
	function __call($method, $args)
	{
		return $this->section($method);
	}

	/**
	 * @param array $handles
	 * @return bContentSectionsTag
	 */
	public function sections($handles = array())
	{
		if (!$handles)
			$sections = Blocks::app()->content->getAllSectionsBySiteId($this->_siteId);
		else
			$sections = Blocks::app()->content->getSectionsBySiteIdHandles($this->_siteId, $handles);

		return new bContentSectionsTag($sections);
	}

	/**
	 * @param $handle
	 * @return bContentSectionTag
	 */
	public function section($handle)
	{
		$section = Blocks::app()->content->getSectionBySiteIdHandle($this->_siteId, $handle);
		return new bContentSectionTag($section);
	}

	/**
	 * @return bContentEntriesTag
	 */
	public function entries()
	{
		$entries = Blocks::app()->content->getAllEntriesBySiteId($this->_siteId);
		return new bContentEntriesTag($entries);
	}

	/**
	 * @param $entryId
	 * @return bContentEntryTag
	 */
	public function entry($entryId)
	{
		$entry = Blocks::app()->content->getEntryById($entryId);
		return new bContentEntryTag($entry);
	}
}
