<?php

class ContentSectionTag extends Tag
{
	public function __toString()
	{
		return $this->handle();
	}

	public function handle()
	{
		return $this->_val->handle;
	}

	public function label()
	{
		return $this->_val->label;
	}

	public function urlFormat()
	{
		return $this->_val->url_format;
	}

	public function template()
	{
		return $this->_val->template;
	}

	public function maxEntries()
	{
		return $this->_val->max_extries;
	}

	public function parent()
	{
		return $this->_val->parent_id == null ? null : new ContentSectionTag($this->_val->parent_id);
	}

	public function hasSubSections()
	{
		return (bool) Blocks::app()->content->doesSectionHaveSubSections($this->_val->id);
	}

	public function site()
	{
		return new SiteTag($this->_val->site_id);
	}

	public function entries()
	{
		$entries = Blocks::app()->content->getEntriesBySectionId($this->_val->id);
		return new ContentEntriesTag($entries);
	}

	public function blocks()
	{
		$blocks = Blocks::app()->content->getBlocksBySectionId($this->_val->id);
		return new ContentBlocksTag($blocks);
	}
}
