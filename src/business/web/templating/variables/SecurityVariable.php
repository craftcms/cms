<?php
namespace Blocks;

/**
 * Security functions
 */
class SecurityVariable
{
	/**
	 * Validates a user activation code
	 */
	public function validateUserActivationCode($code)
	{
		return b()->security->validateUserActivationCode($code);
	}

	/**
	 * Returns a user by its activation code
	 */
	public function getUserByActivationCode($code)
	{
		return b()->security->getUserByActivationCode($code);
	}
}
