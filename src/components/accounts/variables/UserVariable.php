<?php
namespace Blocks;

/**
 * User template variable
 */
class UserVariable extends BaseModelVariable
{
	/**
	 * Use the full name or username as the string representation of the user.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$fullName = $this->fullName();
		if ($fullName)
		{
			return $fullName;
		}
		else
		{
			return $this->model->username;
		}
	}

	/**
	 * Returns the user's profile.
	 *
	 * @return UserProfileVariable|null
	 */
	public function profile()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			$profile = $this->model->getProfile();
			if ($profile)
			{
				return new UserProfileVariable($profile);
			}
		}
	}

	/**
	 * Gets the user's full name.
	 *
	 * @return string|null
	 */
	public function fullName()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			return $this->model->getFullName();
		}
	}

	/**
	 * Gets the user's first name or username.
	 *
	 * @return string|null
	 */
	public function friendlyName()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			return $this->model->getFriendlyName();
		}
	}

	/**
	 * Returns whether this is the current logged-in user.
	 *
	 * @return bool
	 */
	public function isCurrent()
	{
		return $this->model->isCurrent();
	}

	/**
	 * Returns the remaining cooldown time for this user, if they've entered their password incorrectly too many times.
	 *
	 * @return mixed
	 */
	public function remainingCooldownTime()
	{
		return $this->model->getRemainingCooldownTime();
	}

	/**
	 * Returns the user's group IDs
	 *
	 * @return array|null
	 */
	public function groupIds()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			$groupIds = array();

			foreach ($this->model->getGroups() as $group)
			{
				$groupIds[] = $group->id;
			}

			return $groupIds;
		}
	}
}
