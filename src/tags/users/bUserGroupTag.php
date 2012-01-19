<?php

/**
 *
 */
class bUserGroupTag extends bTag
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
	 * @return bUsersTag
	 */
	public function users()
	{
		$groups = Blocks::app()->users->getUsersByGroupId($this->_val->id);
		return new bUsersTag($groups);
	}
}
