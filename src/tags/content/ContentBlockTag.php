<?php

/**
 *
 */
class ContentBlockTag extends Tag
{
	/**
	 * @return mixed
	*/
	public function __toString()
	{
		return $this->handle();
	}

	/**
	 * @return mixed
	*/
	public function handle()
	{
		return $this->_val->handle;
	}

	/**
	 * @return mixed
	 */
	public function label()
	{
		return $this->_val->label;
	}

	/**
	 * @return mixed
	 */
	public function type()
	{
		return $this->_val->type;
	}

	/**
	 * @return mixed
	 */
	public function instructions()
	{
		return $this->_val->instructions;
	}

	/**
	 * @return bool
	 */
	public function required()
	{
		return (bool) $this->_val->required;
	}

	/**
	 * @return mixed
	 */
	public function sortOrder()
	{
		return $this->_val->sort_order;
	}

	/**
	 * @return ContentSectionTag
	 */
	public function section()
	{
		$section = Blocks::app()->content->getSectionById($this->_val->section_id);
		return new ContentSectionTag($section);
	}
}
