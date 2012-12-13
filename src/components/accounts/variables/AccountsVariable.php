<?php
namespace Blocks;

/**
 * User account functions
 */
class AccountsVariable
{
	/**
	 * Gets a user by a verification code.
	 *
	 * @param string $code
	 * @return UserModel|null
	 */
	public function getUserByVerificationCode($code)
	{
		return blx()->accounts->getUserByVerificationCode($code);
	}
}
