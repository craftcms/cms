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
	 * Returns the user's profile.
	 *
	 * @return UserProfileRecord|null
	 */
	function profile()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			return $this->model->profile;
		}
	}

	/**
	 * Gets the user's username or first name.
	 *
	 * @return string|null
	 */
	function name()
	{
		if (Blocks::hasPackage(BlocksPackage::Users) &&
			$this->model->profile &&
			$this->model->profile->firstName
		)
		{
			return $this->model->profile->firstName;
		}
		else
		{
			return $this->model->username;
		}
	}

	/**
	 * Gets the user's full name.
	 *
	 * @return string|null
	 */
	function fullName()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			if ($this->model->profile)
			{
				return $this->model->profile->getFullName();
			}
		}
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

	/**
	 * Returns the user's group IDs
	 *
	 * @return array
	 */
	public function groupIds()
	{
		$groupIds = array();

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			foreach ($this->model->groups as $group)
			{
				$groupIds[] = $group->id;
			}
		}

		return $groupIds;
	}
}
