<?php

/**
 *
 */
class bContentSectionTag extends bTag
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
	 * @return bContentSectionTag|null
	 */
	public function parent()
	{
		return $this->_val->parent_id == null ? null : new bContentSectionTag($this->_val->parent_id);
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
	 * @return bContentEntriesTag
	 */
	public function entries()
	{
		$entries = Blocks::app()->content->getEntriesBySectionId($this->_val->id);
		return new bContentEntriesTag($entries);
	}

	/**
	 * @return bContentBlocksTag
	 */
	public function blocks()
	{
		$blocks = Blocks::app()->content->getBlocksBySectionId($this->_val->id);
		return new bContentBlocksTag($blocks);
	}
}
