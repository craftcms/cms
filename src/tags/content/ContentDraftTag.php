<?php

class ContentDraftTag extends Tag
{
	public function __toString()
	{
		return $this->label();
	}

	public function entry()
	{
		$entry = Blocks::app()->content->getEntryById($this->_val->entry_id);
		return new ContentEntryTag($entry);
	}

	public function author()
	{
		
	}

	public function label()
	{
		return $this->_val->label;
	}
}
