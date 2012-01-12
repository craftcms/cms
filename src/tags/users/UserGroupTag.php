<?php

/**
 *
 */
class UserGroupTag extends Tag
{
	/**
	 * @access public
	 *
	 * @param $method
	 * @param $args
	 *
	 * @return mixed
	 */
	function __call($method, $args)
	{
		return $this->name($method);
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->name();
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function name()
	{
		return $this->_val->name;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function description()
	{
		return $this->_val->description;
	}

	/**
	 * @access public
	 *
	 * @return UsersTag
	 */
	public function users()
	{
		$groups = Blocks::app()->membership->getUsersByGroupId($this->_val->id);
		return new UsersTag($groups);
	}
}
