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
	 * Gets users.
	 *
	 * @param array $params
	 * @return array
	 */
	public function users($params = array())
	{
		return blx()->accounts->getUsers($params);
	}

	/**
	 * Gets the total number of users.
	 *
	 * @param array $params
	 * @return int
	 */
	public function totalUsers($params = array())
	{
		return blx()->accounts->getTotalUsers($params);
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
