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
	 * @return UserModel|null
	 */
	public function getUserByVerificationCode($code)
	{
		return blx()->account->getUserByVerificationCode($code);
	}
}
