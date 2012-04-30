<?php
namespace Blocks;

/**
 * User session functions
 */
class SessionVariable
{
	/**
	 * Returns whether the user is logged in
	 */
	public function loggedin()
	{
		return b()->user->getIsLoggedIn();
	}

	/**
	 * Returns the remembered username from cookie
	 */
	public function rememberedusername()
	{
		return b()->user->getRememeberedUsername();
	}

	/**
	 * Returns any active notifications for the user
	 */
	public function notifications()
	{
		return b()->user->getMessages();
	}
}
