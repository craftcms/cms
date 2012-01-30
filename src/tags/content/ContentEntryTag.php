<?php
namespace Blocks;

/**
 *
 */
class ContentEntryTag extends Tag
{
	/**
	 * @param $method
	 * @param $args
	 * @return ContentBlockTag
	 */
	function __call($method, $args)
	{
		return $this->block($method);
	}

	/**
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->title();
	}

	/**
	 * @return ContentEntryTag|null
	 */
	public function parentEntry()
	{
		return $this->_val->parent_id == null ? null : new ContentEntryTag($this->_val->parent_id);
	}

	/**
	 * @return bool
	 */
	public function hasSubEntries()
	{
		return (bool) Blocks::app()->content->doesEntryHaveSubEntries($this->_val->id);
	}

	/**
	 * @return ContentSectionTag
	 */
	public function section()
	{
		return new ContentSectionTag($this->_val->section_id);
	}

	/**
	 * @return UserTag
	 */
	public function author()
	{
		return new UserTag($this->_val->author_id);
	}

	/**
	 * @return mixed
	 */
	public function slug()
	{
		return $this->_val->slug;
	}

	/**
	 * @return mixed
	 */
	public function uri()
	{
		return $this->_val->full_uri;
	}

	/**
	 * @return bDateTag
	 */
	public function postDate()
	{
		return new bDateTag($this->_val->post_date);
	}

	/**
	 * @return bDateTag
	 */
	public function expirationDate()
	{
		return new DateTag($this->_val->expiration_date);
	}

	/**
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
	 * @return bool
	 */
	public function enabled()
	{
		return (bool) $this->_val->enabled;
	}

	/**
	 * @param string $languageCode
	 * @return mixed
	 */
	public function title($languageCode = 'en-us')
	{
		return Blocks::app()->content->getEntryTitleByLanguageCode($this->_val, $languageCode);
	}

	/**
	 * @return ContentBlocksTag
	 */
	public function blocks()
	{
		$blocks = Blocks::app()->content->getBlocksByEntryId($this->_val->id);
		return new ContentBlocksTag($blocks);
	}

	/**
	 * @param $handle
	 * @return ContentBlockTag
	 */
	public function block($handle)
	{
		$block = Blocks::app()->content->getBlockByEntryIdHandle($this->_val->id, $handle);
		return new ContentBlockTag($block);
	}

	/**
	 * @return ContentVersionsTag
	 */
	public function versions()
	{
		$versions = Blocks::app()->content->getEntryVersionsByEntryId($this->_val->id);
		return new ContentVersionsTag($versions);
	}
}
