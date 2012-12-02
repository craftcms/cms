<?php
namespace Blocks;

/**
 * User criteria class
 */
class UserCriteria extends BaseCriteria
{
	public $id;
	public $groupId;
	public $group;
	public $username;
	public $firstName;
	public $lastName;
	public $email;
	public $admin;
	public $status = 'active';
	public $lastLoginDate;
	public $order = 'username asc';
	public $indexBy;

	/**
	 * Returns all users that match the criteria.
	 *
	 * @access protected
	 * @return array
	 */
	protected function findEntities()
	{
		return blx()->users->findUsers($this);
	}

	/**
	 * Returns the first section that matches the criteria.
	 *
	 * @access protected
	 * @return UserModel|null
	 */
	protected function findFirstEntity()
	{
		return blx()->users->findUser($this);
	}

	/**
	 * Returns the total users that match the criteria.
	 *
	 * @access protected
	 * @return int
	 */
	protected function getTotalEntities()
	{
		return blx()->users->getTotalUsers($this);
	}
}
