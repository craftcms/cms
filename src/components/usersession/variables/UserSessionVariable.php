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
	public function isLoggedIn()
	{
		return blx()->user->isLoggedIn();
	}

	/**
	 * Returns the remembered username from cookie.
	 *
	 * @return string
	 */
	public function getRememberedUsername()
	{
		return blx()->user->getRememberedUsername();
	}

	/**
	 * Returns all flash data for the user.
	 *
	 * @return array
	 */
	public function getFlashes()
	{
		return blx()->user->getFlashes();
	}

	/**
	 * Returns a flash message by a given key.
	 *
	 * @param string $key
	 * @param mixed
	 */
	public function getFlash($key, $defaultValue = null)
	{
		return blx()->user->getFlash($key, $defaultValue);
	}

	/**
	 * Returns whether a flash message exists by a given key.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function hasFlash($key)
	{
		return blx()->user->hasFlash($key);
	}
}
