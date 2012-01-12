<?php

/**
 *
 */
class UserGroupTag extends Tag
{
	/**
	 * @param $method
	 * @param $args
	 * @return mixed
	 */
	function __call($method, $args)
	{
		return $this->name($method);
	}

	/**
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->name();
	}

	/**
	 * @return mixed
	 */
	public function name()
	{
		return $this->_val->name;
	}

	/**
	 * @return mixed
	 */
	public function description()
	{
		return $this->_val->description;
	}

	/**
	 * @return UsersTag
	 */
	public function users()
	{
		$groups = Blocks::app()->membership->getUsersByGroupId($this->_val->id);
		return new UsersTag($groups);
	}
}
