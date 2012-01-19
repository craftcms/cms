<?php

/**
 *
 */
class bUsersTag extends bTag
{
	protected $_users;
	protected $_groups;

	/**
	 * @return mixed
	 */
	public function getUsers()
	{
		if (!isset($this->_users))
		{
			$this->_users = Blocks::app()->users->allUsers;
		}

		return $this->_users;
	}

	/**
	 * @return mixed
	 */
	public function getGroups()
	{
		if (!isset($this->_groups))
		{
			$this->_groups = Blocks::app()->users->allGroups;
		}

		return $this->_groups;
	}
}
