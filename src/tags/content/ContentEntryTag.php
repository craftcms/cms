<?php

/**
 *
 */
class ContentEntryTag extends Tag
{
	/**
	 * @access public
	 *
	 * @param $method
	 * @param $args
	 *
	 * @return ContentBlockTag
	 */
	function __call($method, $args)
	{
		return $this->block($method);
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->title();
	}

	/**
	 * @access public
	 *
	 * @return ContentEntryTag|null
	 */
	public function parentEntry()
	{
		return $this->_val->parent_id == null ? null : new ContentEntryTag($this->_val->parent_id);
	}

	/**
	 * @access public
	 *
	 * @return bool
	 */
	public function hasSubEntries()
	{
		return (bool) Blocks::app()->content->doesEntryHaveSubEntries($this->_val->id);
	}

	/**
	 * @access public
	 *
	 * @return ContentSectionTag
	 */
	public function section()
	{
		return new ContentSectionTag($this->_val->section_id);
	}

	/**
	 * @access public
	 *
	 * @return UserTag
	 */
	public function author()
	{
		return new UserTag($this->_val->author_id);
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function slug()
	{
		return $this->_val->slug;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function uri()
	{
		return $this->_val->full_uri;
	}

	/**
	 * @access public
	 *
	 * @return DateTag
	 */
	public function postDate()
	{
		return new DateTag($this->_val->post_date);
	}

	/**
	 * @access public
	 *
	 * @return DateTag
	 */
	public function expirationDate()
	{
		return new DateTag($this->_val->expiration_date);
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function order()
	{
		return $this->_val->order;
	}

	/**
	 * @return bool
	 */
	public function archived()
	{
		return (bool) $this->_val->archived;
	}

	/**
	 * @access public
	 *
	 * @return bool
	 */
	public function enabled()
	{
		return (bool) $this->_val->enabled;
	}

	/**
	 * @access public
	 *
	 * @param string $languageCode
	 *
	 * @return mixed
	 */
	public function title($languageCode = 'en-us')
	{
		return Blocks::app()->content->getEntryTitleByLanguageCode($this->_val, $languageCode);
	}

	/**
	 * @access public
	 *
	 * @return ContentBlocksTag
	 */
	public function blocks()
	{
		$blocks = Blocks::app()->content->getBlocksByEntryId($this->_val->id);
		return new ContentBlocksTag($blocks);
	}

	/**
	 * @access public
	 *
	 * @param $handle
	 *
	 * @return ContentBlockTag
	 */
	public function block($handle)
	{
		$block = Blocks::app()->content->getBlockByEntryIdHandle($this->_val->id, $handle);
		return new ContentBlockTag($block);
	}

	/**
	 * @access public
	 *
	 * @return ContentVersionsTag
	 */
	public function versions()
	{
		$versions = Blocks::app()->content->getEntryVersionsByEntryId($this->_val->id);
		return new ContentVersionsTag($versions);
	}
}
