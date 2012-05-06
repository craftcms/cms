<?php
namespace Blocks;

/**
 * User functions
 */
class UsersVariable
{
	/**
	 * Returns the current logged-in user.
	 * @return User
	 */
	public function current()
	{
		return b()->users->getCurrent();
	}

	/**
	 * Returns a user by its ID.
	 * @param $userId
	 * @return User
	 */
	public function getById($userId)
	{
		return b()->users->getById($userId);
	}

	/**
	 * Returns the recent users.
	 * @return array
	 */
	public function recent()
	{
		return b()->users->getRecent();
	}

	/**
	 * Returns the URL segment for account verification.
	 * @return string
	 */
	public function verifyAccountUrl()
	{
		return b()->users->getVerifyAccountUrl();
	}
}
