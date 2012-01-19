<?php

/**
 *
 */
class bContentDraftTag extends bTag
{
	/**
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->label();
	}

	/**
	 * @return bContentEntryTag
	 */
	public function entry()
	{
		$entry = Blocks::app()->content->getEntryById($this->_val->entry_id);
		return new bContentEntryTag($entry);
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
