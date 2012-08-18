<?php
namespace Blocks;

/**
 * Security functions
 */
class SecurityVariable
{
	/**
	 * Validates a user verification code.
	 * @param string $code
	 * @return bool
	 */
	public function validateUserVerificationCode($code)
	{
		return blx()->security->validateUserVerificationCode($code);
	}

	/**
	 * Returns a user by its verification code
	 * @param string $code
	 * @return User
	 */
	public function getUserByVerificationCode($code)
	{
		return blx()->security->getUserByVerificationCode($code);
	}
}
