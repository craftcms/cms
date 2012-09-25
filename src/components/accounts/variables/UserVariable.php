<?php
namespace Blocks;

/**
 * User template variable
 */
class UserVariable extends ModelVariable
{
	/**
	 * Use the username as the string representation of the user.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->model->username;
	}

	/**
	 * Gets the user's full name.
	 *
	 * @return string
	 */
	function fullName()
	{
		return $this->model->getFullName();
	}

	/**
	 * Returns whether this is the current logged-in user.
	 *
	 * @return bool
	 */
	function isCurrent()
	{
		return $this->model->isCurrent();
	}

	/**
	 * Returns the remaining cooldown time for this user, if they've entered their password incorrectly too many times.
	 *
	 * @return mixed
	 */
	function remainingCooldownTime()
	{
		return $this->model->getRemainingCooldownTime();
	}
	/* BLOCKSPRO ONLY */

	/**
	 * Returns the user's group IDs
	 *
	 * @return array
	 */
	public function groupIds()
	{
		$groupIds = array();

		foreach ($this->model->groups as $group)
		{
			$groupIds[] = $group->id;
		}

		return $groupIds;
	}
	/* end BLOCKSPRO ONLY */
}
