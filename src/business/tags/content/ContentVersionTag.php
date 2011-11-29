<?php

class ContentVersionTag extends Tag
{
	public function __toString()
	{
		return $this->label();
	}

	public function page()
	{
		$page = Blocks::app()->content->getPageById($this->_val->page_id);
		return new ContentPageTag($page);
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
		return new BoolTag($this->_val->is_list);
	}
}
