<?php

class MembershipTag extends Tag
{
	private $_siteId;

	function __construct($siteId)
	{
		$this->_siteId = $siteId;
	}

	public function users()
	{
		$users = Blocks::app()->membership->getAllUsers();
		return new UsersTag($users);
	}

	public function usersForSite($siteId)
	{
		$users = Blocks::app()->membership->getAllUsersBySiteId($siteId);
		return new UsersTag($users);
	}

	public function groups()
	{
		$groups = Blocks::app()->membership->getAllGroups();
		return new GroupsTag($groups);
	}
}
