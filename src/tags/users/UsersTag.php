<?php
namespace Blocks;

/**
 *
 */
class UsersTag extends Tag
{
	/**
	 * Get user by ID
	 *
	 * @param $userId
	 * @return mixed
	 */
	function getById($userId)
	{
		return Blocks::app()->users->getUserById($userId);
	}

	/**
	 * Get all users
	 * @return
	 */
	function __toArray()
	{
		return Blocks::app()->users->getAllUsers();
	}
}
