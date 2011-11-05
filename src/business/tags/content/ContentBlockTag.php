<?php

class ContentBlockTag extends Tag
{
	public function __toString()
	{
		return $this->handle();
	}

	public function handle()
	{
		return new StringTag($this->_val->handle);
	}

	public function label()
	{
		return new StringTag($this->_val->label);
	}

	public function type()
	{
		return new StringTag($this->_val->type);
	}

	public function instructions()
	{
		return new StringTag($this->_val->instructions);
	}

	public function required()
	{
		return new BoolTag($this->_val->required);
	}

	public function displayOrder()
	{
		return new NumericTag($this->_val->display_order);
	}

	public function section()
	{
		$section = Blocks::app()->content->getSectionById($this->_val->section_id);
		return new ContentSectionTag($section);
	}
}
