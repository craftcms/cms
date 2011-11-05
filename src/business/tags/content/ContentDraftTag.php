<?php

class ContentDraftTag extends Tag
{
	public function __toString()
	{
		return $this->label();
	}

	public function page()
	{
		$page = Blocks::app()->contentRepo->getPageById($this->_val->page_id);
		return new ContentPageTag($page);
	}

	public function author()
	{
		
	}

	public function label()
	{
		return new StringTag($this->_val->label);
	}
}
