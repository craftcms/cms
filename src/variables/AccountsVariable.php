<?php
namespace Blocks;

/**
 * User functions
 */
class AccountsVariable
{
	/**
	 * Returns the current logged-in user.
	 * @return User
	 */
	public function current()
	{
		return blx()->accounts->getCurrentUser();
	}

	/**
	 * Returns all the users.
	 *
	 * @return array
	 */
	public function users()
	{
		return blx()->accounts->getUsers();
	}

	/**
	 * Returns all the admins.
	 *
	 * @return array
	 */
	public function admins()
	{
		return blx()->accounts->getAdmins();
	}

	/**
	 * Returns a user by its ID.
	 * @param $userId
	 * @return User
	 */
	public function getById($userId)
	{
		return blx()->accounts->getUserById($userId);
	}

	/**
	 * Gets a user by a verification code.
	 *
	 * @param string $code
	 * @return User
	 */
	public function getUserByVerificationCode($code)
	{
		return blx()->accounts->getUserByVerificationCode($code);
	}

	/**
	 * Returns the recent users.
	 * @return array
	 */
	public function recent()
	{
		return blx()->accounts->getRecentUsers();
	}

	/**
	 * Returns the URL segment for account verification.
	 * @return string
	 */
	public function verifyAccountUrl()
	{
		return blx()->accounts->getVerifyAccountUrl();
	}
}
