<?php
namespace Blocks;

/**
 * User session functions
 */
class SessionVariable
{
	/**
	 * Returns whether the user is logged in.
	 * @return bool
	 */
	public function loggedin()
	{
		return b()->user->getIsLoggedIn();
	}

	/**
	 * Returns the remembered username from cookie.
	 * @return string
	 */
	public function rememberedusername()
	{
		return b()->user->getRememeberedUsername();
	}

	/**
	 * Returns any active notifications for the user.
	 * @return array
	 */
	public function notifications()
	{
		return b()->user->getMessages();
	}
}
