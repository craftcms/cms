<?php
namespace Blocks;

/**
 * User account functions
 */
class AccountVariable
{
	/**
	 * Gets a user by a verification code.
	 *
	 * @param string $code
	 * @return UserVariable|null
	 */
	public function getUserByVerificationCode($code)
	{
		$user = blx()->account->getUserByVerificationCode($code);

		if ($user)
		{
			return new UserVariable($user);
		}
	}
}
