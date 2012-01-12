<?php

/**
 *
 */
class UserTag extends Tag
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
		return $this->userName($method);
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->userName();
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function userName()
	{
		return $this->_val->username;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function email()
	{
		return $this->_val->email;
	}

	/**
	 * @return mixed
	 */
	public function firstName()
	{
		return $this->_val->first_name;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function lastName()
	{
		return $this->_val->last_name;
	}

	/**
	 * @access public
	 *
	 * @return GroupsTag
	 */
	public function groups()
	{
		$groups = Blocks::app()->membership->getGroupsByUserId($this->_val->id);
		return new GroupsTag($groups);
	}
}
