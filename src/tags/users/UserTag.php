<?php
namespace Blocks;

/**
 *
 */
class UserTag extends Tag
{
	/**
	 * @param $method
	 * @param $args
	 * @return mixed
	 */
	function __call($method, $args)
	{
		return $this->userName($method);
	}

	/**
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->userName();
	}

	/**
	 * @return mixed
	 */
	public function userName()
	{
		return $this->_val->username;
	}

	/**
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
	 * @return mixed
	 */
	public function lastName()
	{
		return $this->_val->last_name;
	}

	/**
	 * @return GroupsTag
	 */
	public function groups()
	{
		$groups = Blocks::app()->users->getGroupsByUserId($this->_val->id);
		return new GroupsTag($groups);
	}
}
