<?php

/**
 *
 */
class ContentDraftTag extends Tag
{
	/**
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->label();
	}

	/**
	 * @return ContentEntryTag
	 */
	public function entry()
	{
		$entry = Blocks::app()->content->getEntryById($this->_val->entry_id);
		return new ContentEntryTag($entry);
	}

	/**
	 */
	public function author()
	{
		
	}

	/**
	 * @return mixed
	 */
	public function label()
	{
		return $this->_val->label;
	}
}
