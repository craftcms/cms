<?php

/**
 *
 */
class ContentBlockTag extends Tag
{
	/**
	 * @access public
	 *
	 * @return mixed
	*/
	public function __toString()
	{
		return $this->handle();
	}

	/**
	 * @access public
	 *
	 * @return mixed
	*/
	public function handle()
	{
		return $this->_val->handle;
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
	 * @return mixed
	 */
	public function type()
	{
		return $this->_val->type;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function instructions()
	{
		return $this->_val->instructions;
	}

	/**
	 * @access public
	 *
	 * @return bool
	 */
	public function required()
	{
		return (bool) $this->_val->required;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function sortOrder()
	{
		return $this->_val->sort_order;
	}

	/**
	 * @access public
	 *
	 * @return ContentSectionTag
	 */
	public function section()
	{
		$section = Blocks::app()->content->getSectionById($this->_val->section_id);
		return new ContentSectionTag($section);
	}
}
