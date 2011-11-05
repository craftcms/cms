<?php

class ContentPageTag extends Tag
{
	function __call($method, $args)
	{
		return $this->block($method);
	}

	public function __toString()
	{
		return $this->title();
	}

	public function parentPage()
	{
		return $this->_val->parent_id == null ? null : new ContentPageTag($this->_val->parent_id);
	}

	public function hasSubPages()
	{
		$hasSubPages = Blocks::app()->content->doesPageHaveSubPages($this->_val->id);
		return new BoolTag($hasSubPages);
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
		return new StringTag($this->_val->slug);
	}

	public function uri()
	{
		return new StringTag($this->_val->full_uri);
	}

	public function postDate()
	{
		return new DateTag($this->_val->post_date);
	}

	public function expirationDate()
	{
		return new DateTag($this->_val->expiration_date);
	}

	public function pageOrder()
	{
		return new NumericTag($this->_val->page_order);
	}

	public function archived()
	{
		return new BoolTag($this->_val->archived);
	}

	public function enabled()
	{
		return new BoolTag($this->_val->enabled);
	}

	public function title($languageCode = 'en-us')
	{
		$pageTitle = Blocks::app()->content->getPageTitleByLanguageCode($this->_val, $languageCode);
		return new StringTag($pageTitle);
	}

	public function blocks()
	{
		$blocks = Blocks::app()->content->getBlocksByPageId($this->_val->id);
		return new ContentBlocksTag($blocks);
	}

	public function block($handle)
	{
		$block = Blocks::app()->content->getBlockByHandle($this->_val->id, $handle);
		return new ContentBlockTag($block);
	}

	public function versions()
	{
		$versions = Blocks::app()->content->getPageVersionsByPageId($this->_val->id);
		return new ContentVersionsTag($versions);
	}

	public function version($id)
	{
		$version = Blocks::app()->content->getPageVersionById($id);
		return new ContentVersionTag($version);
	}
}
