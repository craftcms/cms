<?php

/**
 *
 */
class ContentSectionTag extends Tag
{
	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->handle();
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function handle()
	{
		return $this->_val->handle;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function label()
	{
		return $this->_val->label;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function urlFormat()
	{
		return $this->_val->url_format;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function template()
	{
		return $this->_val->template;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function maxEntries()
	{
		return $this->_val->max_extries;
	}

	/**
	 * @access public
	 *
	 * @return ContentSectionTag|null
	 */
	public function parent()
	{
		return $this->_val->parent_id == null ? null : new ContentSectionTag($this->_val->parent_id);
	}

	/**
	 * @access public
	 *
	 * @return bool
	 */
	public function hasSubSections()
	{
		return (bool) Blocks::app()->content->doesSectionHaveSubSections($this->_val->id);
	}

	/**
	 * @access public
	 *
	 * @return SiteTag
	 */
	public function site()
	{
		return new SiteTag($this->_val->site_id);
	}

	/**
	 * @access public
	 *
	 * @return ContentEntriesTag
	 */
	public function entries()
	{
		$entries = Blocks::app()->content->getEntriesBySectionId($this->_val->id);
		return new ContentEntriesTag($entries);
	}

	/**
	 * @access public
	 *
	 * @return ContentBlocksTag
	 */
	public function blocks()
	{
		$blocks = Blocks::app()->content->getBlocksBySectionId($this->_val->id);
		return new ContentBlocksTag($blocks);
	}
}
