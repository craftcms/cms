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
		$record = blx()->account->getUserByVerificationCode($code);
		if ($record)
		{
			return new UserVariable($user);
		}
	}
}
