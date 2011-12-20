<?php

class ContentBlockTag extends Tag
{
	public function __toString()
	{
		return $this->handle();
	}

	public function handle()
	{
		return $this->_val->handle;
	}

	public function label()
	{
		return $this->_val->label;
	}

	public function type()
	{
		return $this->_val->type;
	}

	public function instructions()
	{
		return $this->_val->instructions;
	}

	public function required()
	{
		return (bool) $this->_val->required;
	}

	public function sortOrder()
	{
		return $this->_val->sort_order;
	}

	public function section()
	{
		$section = Blocks::app()->content->getSectionById($this->_val->section_id);
		return new ContentSectionTag($section);
	}
}
