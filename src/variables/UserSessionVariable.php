<?php
namespace Craft;

/**
 * User session functions.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.variables
 * @since     1.0
 */
class UserSessionVariable
{
	// Public Methods
	// =========================================================================

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
	 * Returns the number of seconds the user will be logged in for.
	 *
	 * @return int
	 */
	public function getAuthTimeout()
	{
		if (craft()->isInstalled())
		{
			return craft()->userSession->getAuthTimeout();
		}
		else
		{
			return 0;
		}
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
	 * @param string $defaultUrl The default URL that should be returned if no return URL was stored.
	 * @param bool   $delete     Whether the stored return URL should be deleted after it was fetched.
	 *
	 * @return string The return URL, or $defaultUrl.
	 */
	public function getReturnUrl($defaultUrl = null, $delete = false)
	{
		return craft()->userSession->getReturnUrl($defaultUrl, $delete);
	}

	/**
	 * Returns all flash data for the user.
	 *
	 * @param bool $delete
	 *
	 * @return array
	 */
	public function getFlashes($delete = true)
	{
		return craft()->userSession->getFlashes($delete);
	}

	/**
	 * Returns a flash message by a given key.
	 *
	 * @param string $key
	 * @param mixed  $defaultValue
	 * @param bool   $delete
	 *
	 * @return mixed
	 */
	public function getFlash($key, $defaultValue = null, $delete = true)
	{
		return craft()->userSession->getFlash($key, $defaultValue, $delete);
	}

	/**
	 * Returns whether a flash message exists by a given key.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function hasFlash($key)
	{
		return craft()->userSession->hasFlash($key);
	}
}
