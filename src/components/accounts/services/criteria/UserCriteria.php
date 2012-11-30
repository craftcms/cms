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
	public $offset;
	public $limit = 100;
	public $indexBy;

	/**
	 * Returns all users that match the criteria.
	 *
	 * @param array|null $criteria
	 * @return array
	 */
	public function find($criteria = null)
	{
		$this->setCriteria($criteria);
		return blx()->users->findUsers($this);
	}

	/**
	 * Returns the first section that matches the criteria.
	 *
	 * @param array|null $criteria
	 * @return UserModel|null
	 */
	public function first($criteria = null)
	{
		$this->setCriteria($criteria);
		return blx()->users->findUser($this);
	}

	/**
	 * Returns the total users that match the criteria.
	 *
	 * @param array|null $criteria
	 * @return int
	 */
	public function total($criteria = null)
	{
		$this->setCriteria($criteria);
		return blx()->users->getTotalUsers($this);
	}
}
