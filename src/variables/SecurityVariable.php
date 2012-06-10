<?php
namespace Blocks;

/**
 * Security functions
 */
class SecurityVariable
{
	/**
	 * Validates a user activation code.
	 * @param string $code
	 * @return bool
	 */
	public function validateUserActivationCode($code)
	{
		return blx()->security->validateUserActivationCode($code);
	}

	/**
	 * Returns a user by its activation code
	 * @param string $code
	 * @return User
	 */
	public function getUserByActivationCode($code)
	{
		return blx()->security->getUserByActivationCode($code);
	}
}
