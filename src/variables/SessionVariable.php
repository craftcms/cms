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
		return blx()->user->getIsLoggedIn();
	}

	/**
	 * Returns the remembered username from cookie.
	 * @return string
	 */
	public function rememberedusername()
	{
		return blx()->user->getRememberedUsername();
	}

	/**
	 * Returns any active notifications for the user.
	 * @return array
	 */
	public function messages()
	{
		return blx()->user->getFlashes();
	}
}
