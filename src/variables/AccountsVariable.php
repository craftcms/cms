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
	 * Gets admins.
	 *
	 * @param array $params
	 * @return array
	 */
	public function admins($params = array())
	{
		return blx()->accounts->getAdmins($params);
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
