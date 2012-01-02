<?php

class UsersTag extends Tag
{
	protected $_users;
	protected $_groups;

	protected function getUsers()
	{
		if (!isset($this->_users))
		{
			$this->_users = Blocks::app()->membership->allUsers;
		}

		return $this->_users;
	}

	protected function getGroups()
	{
		if (!isset($this->_groups))
		{
			$this->_groups = Blocks::app()->membership->allGroups;
		}

		return $this->_groups;
	}
}
