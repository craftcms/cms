<?php
namespace Craft;

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
		return craft()->userSession->isLoggedIn();
	}

	/**
	 * Returns the currently logged in user.
	 *
	 * @return UserModel|null
	 */
	public function getUser()
	{
		return craft()->userSession->getUser();
	}

	/**
	 * Returns the remembered username from cookie.
	 *
	 * @return string
	 */
	public function getRememberedUsername()
	{
		return craft()->userSession->getRememberedUsername();
	}

	/**
	 * Returns the URL the user was trying to access before getting sent to the login page.
	 *
	 * @param string $defaultUrl
	 * @return mixed
	 */
	public function getReturnUrl($defaultUrl = '')
	{
		return craft()->userSession->getReturnUrl($defaultUrl);
	}

	/**
	 * Returns all flash data for the user.
	 *
	 * @return array
	 */
	public function getFlashes()
	{
		return craft()->userSession->getFlashes();
	}

	/**
	 * Returns a flash message by a given key.
	 *
	 * @param string $key
	 * @param mixed
	 */
	public function getFlash($key, $defaultValue = null)
	{
		return craft()->userSession->getFlash($key, $defaultValue);
	}

	/**
	 * Returns whether a flash message exists by a given key.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function hasFlash($key)
	{
		return craft()->userSession->hasFlash($key);
	}
}
