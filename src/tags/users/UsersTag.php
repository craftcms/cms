<?php

/**
 *
 */
class UsersTag extends Tag
{
	protected $_users;
	protected $_groups;

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function getUsers()
	{
		if (!isset($this->_users))
		{
			$this->_users = Blocks::app()->membership->allUsers;
		}

		return $this->_users;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function getGroups()
	{
		if (!isset($this->_groups))
		{
			$this->_groups = Blocks::app()->membership->allGroups;
		}

		return $this->_groups;
	}
}
