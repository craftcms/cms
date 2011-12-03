<?php

class ContentEntryTag extends Tag
{
	function __call($method, $args)
	{
		return $this->block($method);
	}

	public function __toString()
	{
		return $this->title();
	}

	public function parentEntry()
	{
		return $this->_val->parent_id == null ? null : new ContentEntryTag($this->_val->parent_id);
	}

	public function hasSubEntries()
	{
		return (bool) Blocks::app()->content->doesEntryHaveSubEntries($this->_val->id);
	}

	public function section()
	{
		return new ContentSectionTag($this->_val->section_id);
	}

	public function author()
	{
		return new UserTag($this->_val->author_id);
	}

	public function slug()
	{
		return $this->_val->slug;
	}

	public function uri()
	{
		return $this->_val->full_uri;
	}

	public function postDate()
	{
		return new DateTag($this->_val->post_date);
	}

	public function expirationDate()
	{
		return new DateTag($this->_val->expiration_date);
	}

	public function order()
	{
		return $this->_val->order;
	}

	public function archived()
	{
		return (bool) $this->_val->archived;
	}

	public function enabled()
	{
		return (bool) $this->_val->enabled;
	}

	public function title($languageCode = 'en-us')
	{
		return Blocks::app()->content->getEntryTitleByLanguageCode($this->_val, $languageCode);
	}

	public function blocks()
	{
		$blocks = Blocks::app()->content->getBlocksByEntryId($this->_val->id);
		return new ContentBlocksTag($blocks);
	}

	public function block($handle)
	{
		$block = Blocks::app()->content->getBlockByEntryIdHandle($this->_val->id, $handle);
		return new ContentBlockTag($block);
	}

	public function versions()
	{
		$versions = Blocks::app()->content->getEntryVersionsByEntryId($this->_val->id);
		return new ContentVersionsTag($versions);
	}
}
