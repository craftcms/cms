<?php
namespace Blocks;

/**
 * User functions
 */
class UsersVariable
{
	/**
	 * Returns the current logged-in user
	 * @return User
	 */
	public function current()
	{
		return b()->users->getCurrent();
	}

	/**
	 * Returns the user by its ID
	 */
	public function getById($userId)
	{
		return b()->users->getById($userId);
	}

	/**
	 * Returns the recent users
	 */
	public function recent()
	{
		return b()->users->getRecent();
	}

	/**
	 * Returns any active notifications for the user
	 */
	public function verifyAccountUrl()
	{
		return b()->users->getVerifyAccountUrl();
	}
}
