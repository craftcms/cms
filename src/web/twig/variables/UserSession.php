<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\variables;

use craft\app\elements\User;

/**
 * User session functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserSession
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
		return !\Craft::$app->getUser()->getIsGuest();
	}

	/**
	 * Returns the currently logged in user.
	 *
	 * @return User|null
	 */
	public function getUser()
	{
		return \Craft::$app->getUser()->getIdentity();
	}

	/**
	 * Returns the number of seconds the user will be logged in for.
	 *
	 * @return int
	 */
	public function getRemainingSessionTime()
	{
		if (\Craft::$app->isInstalled())
		{
			return \Craft::$app->getUser()->getRemainingSessionTime();
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
		return \Craft::$app->getUser()->getRememberedUsername();
	}

	/**
	 * Returns the URL the user was trying to access before getting sent to the login page.
	 *
	 * @param string $defaultUrl The default URL that should be returned if no return URL was stored.
	 *
	 * @return string The return URL, or $defaultUrl.
	 */
	public function getReturnUrl($defaultUrl = null)
	{
		return \Craft::$app->getUser()->getReturnUrl($defaultUrl);
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
		return \Craft::$app->getSession()->getAllFlashes($delete);
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
		return \Craft::$app->getSession()->getFlash($key, $defaultValue, $delete);
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
		return \Craft::$app->getSession()->hasFlash($key);
	}
}
