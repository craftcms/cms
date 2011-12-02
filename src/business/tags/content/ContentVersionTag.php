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
		return new NumTag($this->_val->num);
	}

	public function label()
	{
		return new StringTag($this->_val->label);
	}

	public function isLive()
	{
		return new BoolTag($this->_val->is_live);
	}
}
