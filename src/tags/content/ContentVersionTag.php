<?php
namespace Blocks;

/**
 *
 */
class ContentVersionTag extends Tag
{
	/**
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->label();
	}

	/**
	 * @return ContentEntryTag
	 */
	public function entry()
	{
		$entry = Blocks::app()->content->getEntryById($this->_val->entry_id);
		return new ContentEntryTag($entry);
	}

	/**
	 * @return mixed
	 */
	public function num()
	{
		return $this->_val->num;
	}

	/**
	 * @return mixed
	 */
	public function label()
	{
		return $this->_val->label;
	}

	/**
	 * @return bool
	 */
	public function isLive()
	{
		return (bool) $this->_val->is_live;
	}
}
