<?php

class ContentVersionTag extends Tag
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

	public function num()
	{
		return $this->_val->num;
	}

	public function label()
	{
		return $this->_val->label;
	}

	public function isLive()
	{
		return (bool) $this->_val->is_live;
	}
}
