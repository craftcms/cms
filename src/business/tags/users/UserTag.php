<?php

class UserTag extends Tag
{
	function __call($method, $args)
	{
		return $this->userName($method);
	}

	public function __toString()
	{
		return $this->userName();
	}

	public function userName()
	{
		return $this->_val->username;
	}

	public function email()
	{
		return $this->_val->email;
	}

	public function firstName()
	{
		return $this->_val->first_name;
	}

	public function lastName()
	{
		return $this->_val->last_name;
	}

	public function groups()
	{
		$groups = Blocks::app()->membership->getGroupsByUserId($this->_val->id);
		return new GroupsTag($groups);
	}
}
