<?php

/**
 *
 */
class ContentVersionTag extends Tag
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
	 *
	 * @return mixed
	 */
	public function num()
	{
		return $this->_val->num;
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
	 * @return bool
	 */
	public function isLive()
	{
		return (bool) $this->_val->is_live;
	}
}
