<?php

class ContentPageTag extends Tag
{
	public function __toString()
	{
		return $this->title();
	}

	public function parent()
	{
		return $this->_val->parent_id == null ? null : new ContentPageTag($this->_val->parent_id);
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
		$pageTitle = Blocks::app()->contentRepo->getPageTitleByLanguageCode($this->_val, $languageCode);
		return new StringTag($pageTitle);
	}

	public function blocks()
	{
		
	}
}
