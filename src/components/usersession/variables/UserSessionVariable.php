<?php
namespace Blocks;

/**
 * User session functions
 */
class UserSessionVariable
{
	/**
	 * Returns whether the user is logged in.
	 *
	 * @return bool
	 */
	public function loggedin()
	{
		return blx()->user->isLoggedIn();
	}

	/**
	 * Returns the remembered username from cookie.
	 *
	 * @return string
	 */
	public function rememberedusername()
	{
		return blx()->user->getRememberedUsername();
	}

	/**
	 * Returns any flash data for the user.
	 *
	 * @return array
	 */
	public function getFlashes()
	{
		return blx()->user->getFlashes();
	}
}
