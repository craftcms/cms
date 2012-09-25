<?php
namespace Blocks;

/**
 * User functions
 */
class AccountsVariable
{
	/* BLOCKSPRO ONLY */
	// -------------------------------------------
	//  User Groups
	// -------------------------------------------

	/**
	 * Returns all user groups.
	 *
	 * @return array
	 */
	public function userGroups()
	{
		return blx()->userGroups->getAllGroups();
	}

	/**
	 * Gets a user group by its ID.
	 *
	 * @param int $id
	 * @return UserGroupPackage
	 */
	public function getUserGroupById($id)
	{
		return blx()->userGroups->getGroupById($id);
	}

	// -------------------------------------------
	//  User Blocks
	// -------------------------------------------

	/**
	 * Returns all user blocks.
	 *
	 * @return array
	 */
	public function userBlocks()
	{
		return blx()->userBlocks->getAllBlocks();
	}

	/**
	 * Gets a user block by its ID.
	 *
	 * @param int $id
	 * @return BlockVariable
	 */
	public function getUserBlockById($id)
	{
		return blx()->userBlocks->getBlockById($id);
	}

	/* end BLOCKSPRO ONLY */
	// -------------------------------------------
	//  Users
	// -------------------------------------------

	/**
	 * Returns the current logged-in user.
	 *
	 * @return User
	 */
	public function current()
	{
		$record = blx()->accounts->getCurrentUser();
		if ($record)
			return new UserVariable($record);
	}

	/* BLOCKSPRO ONLY */
	/**
	 * Gets users.
	 *
	 * @param array $params
	 * @return array
	 */
	public function users($params = array())
	{
		$params = new UserParams($params);
		$records = blx()->accounts->getUsers($params);
		return VariableHelper::populateVariables($records, 'UserVariable');
	}

	/**
	 * Gets the total number of users.
	 *
	 * @param array $params
	 * @return int
	 */
	public function totalUsers($params = array())
	{
		$params = new UserParams($params);
		return blx()->accounts->getTotalUsers($params);
	}

	/* end BLOCKSPRO ONLY */
	/**
	 * Returns a user by its ID.
	 *
	 * @param $userId
	 * @return User
	 */
	public function getUserById($userId)
	{
		$record = blx()->accounts->getUserById($userId);
		if ($record)
			return new UserVariable($record);
	}

	/**
	 * Gets a user by a verification code.
	 *
	 * @param string $code
	 * @return User
	 */
	public function getUserByVerificationCode($code)
	{
		$record = blx()->accounts->getUserByVerificationCode($code);
		if ($record)
			return new UserVariable($user);
	}

	/**
	 * Returns the URL segment for account verification.
	 *
	 * @return string
	 */
	public function verifyAccountUrl()
	{
		return blx()->accounts->getVerifyAccountUrl();
	}
}
