<?php

/**
 *
 */
class ContentSectionTag extends Tag
{
	/**
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->handle();
	}

	/**
	 * @return mixed
	 */
	public function handle()
	{
		return $this->_val->handle;
	}

	/**
	 * @return mixed
	 */
	public function label()
	{
		return $this->_val->label;
	}

	/**
	 * @return mixed
	 */
	public function urlFormat()
	{
		return $this->_val->url_format;
	}

	/**
	 * @return mixed
	 */
	public function template()
	{
		return $this->_val->template;
	}

	/**
	 * @return mixed
	 */
	public function maxEntries()
	{
		return $this->_val->max_extries;
	}

	/**
	 * @return ContentSectionTag|null
	 */
	public function parent()
	{
		return $this->_val->parent_id == null ? null : new ContentSectionTag($this->_val->parent_id);
	}

	/**
	 * @return bool
	 */
	public function hasSubSections()
	{
		return (bool) Blocks::app()->content->doesSectionHaveSubSections($this->_val->id);
	}

	/**
	 * @return SiteTag
	 */
	public function site()
	{
		return new SiteTag($this->_val->site_id);
	}

	/**
	 * @return ContentEntriesTag
	 */
	public function entries()
	{
		$entries = Blocks::app()->content->getEntriesBySectionId($this->_val->id);
		return new ContentEntriesTag($entries);
	}

	/**
	 * @return ContentBlocksTag
	 */
	public function blocks()
	{
		$blocks = Blocks::app()->content->getBlocksBySectionId($this->_val->id);
		return new ContentBlocksTag($blocks);
	}
}
