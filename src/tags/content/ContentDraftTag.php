<?php

/**
 *
 */
class ContentDraftTag extends Tag
{
	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->label();
	}

	/**
	 * @access public
	 *
	 * @return ContentEntryTag
	 */
	public function entry()
	{
		$entry = Blocks::app()->content->getEntryById($this->_val->entry_id);
		return new ContentEntryTag($entry);
	}

	/**
	 * @access public
	 */
	public function author()
	{
		
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
}
