<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\elements\User;

/**
 * User session functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 3.0.0
 */
class UserSession
{
    /**
     * Returns whether the user is logged in.
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        Craft::$app->getDeprecator()->log('craft.session.isLoggedIn()', '`craft.session.isLoggedIn()` has been deprecated. Use `not craft.app.user.isGuest` instead.');

        return !Craft::$app->getUser()->getIsGuest();
    }

    /**
     * Returns the currently logged in user.
     *
     * @return User|null
     */
    public function getUser()
    {
        Craft::$app->getDeprecator()->log('craft.session.getUser()', '`craft.session.getUser()` has been deprecated. Use `currentUser` instead.');

        return Craft::$app->getUser()->getIdentity();
    }

    /**
     * Returns the number of seconds the user will be logged in for.
     *
     * @return int
     */
    public function getRemainingSessionTime(): int
    {
        Craft::$app->getDeprecator()->log('craft.session.getRemainingSessionTime()', '`craft.session.getRemainingSessionTime()` has been deprecated. Use `craft.app.user.remainingSessionTime` instead.');

        if (Craft::$app->getIsInstalled()) {
            return Craft::$app->getUser()->getRemainingSessionTime();
        }

        return 0;
    }

    /**
     * Returns the remembered username from cookie.
     *
     * @return string|null
     */
    public function getRememberedUsername()
    {
        Craft::$app->getDeprecator()->log('craft.session.getRememberedUsername()', '`craft.session.getRememberedUsername()` has been deprecated. Use `craft.app.user.rememberedUsername` instead.');

        return Craft::$app->getUser()->getRememberedUsername();
    }

    /**
     * Returns the URL the user was trying to access before getting sent to the login page.
     *
     * @param string|null $defaultUrl The default URL that should be returned if no return URL was stored.
     * @return string The return URL, or|null $defaultUrl.
     */
    public function getReturnUrl(string $defaultUrl = null): string
    {
        Craft::$app->getDeprecator()->log('craft.session.getReturnUrl()', '`craft.session.getReturnUrl()` has been deprecated. Use `craft.app.user.getReturnUrl()` instead.');

        return Craft::$app->getUser()->getReturnUrl($defaultUrl);
    }

    /**
     * Returns all flash data for the user.
     *
     * @param bool $delete
     * @return array
     */
    public function getFlashes(bool $delete = true): array
    {
        Craft::$app->getDeprecator()->log('craft.session.getFlashes()', '`craft.session.getFlashes()` has been deprecated. Use `craft.app.session.getAllFlashes()` instead.');

        return Craft::$app->getSession()->getAllFlashes($delete);
    }

    /**
     * Returns a flash message by a given key.
     *
     * @param string $key
     * @param mixed $defaultValue
     * @param bool $delete
     * @return mixed
     */
    public function getFlash(string $key, $defaultValue = null, bool $delete = true)
    {
        Craft::$app->getDeprecator()->log('craft.session.getFlash()', '`craft.session.getFlash()` has been deprecated. Use `craft.app.session.getFlash()` instead.');

        return Craft::$app->getSession()->getFlash($key, $defaultValue, $delete);
    }

    /**
     * Returns whether a flash message exists by a given key.
     *
     * @param string $key
     * @return mixed
     */
    public function hasFlash(string $key)
    {
        Craft::$app->getDeprecator()->log('craft.session.hasFlash()', '`craft.session.hasFlash()` has been deprecated. Use `craft.app.session.hasFlash()` instead.');

        return Craft::$app->getSession()->hasFlash($key);
    }
}
