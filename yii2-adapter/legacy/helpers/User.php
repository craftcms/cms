<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\elements\User as UserElement;
use CraftCms\Cms\Cms;
use function CraftCms\Cms\t;

/**
 * Class User
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.7
 */
class User
{
    /**
     * @param UserElement $user
     * @return string|null
     */
    public static function getAuthStatus(UserElement $user): ?string
    {
        switch ($user->getStatus()) {
            case UserElement::STATUS_INACTIVE:
            case UserElement::STATUS_ARCHIVED:
                return UserElement::AUTH_INVALID_CREDENTIALS;
            case UserElement::STATUS_PENDING:
                return UserElement::AUTH_PENDING_VERIFICATION;
            case UserElement::STATUS_SUSPENDED:
                return UserElement::AUTH_ACCOUNT_SUSPENDED;
            case UserElement::STATUS_ACTIVE:
                if ($user->locked) {
                    // Let them know how much time they have to wait (if any) before their account is unlocked.
                    if (Cms::config()->cooldownDuration) {
                        return UserElement::AUTH_ACCOUNT_COOLDOWN;
                    }
                    return UserElement::AUTH_ACCOUNT_LOCKED;
                }
                // Is a password reset required?
                if ($user->passwordResetRequired) {
                    return UserElement::AUTH_PASSWORD_RESET_REQUIRED;
                }
                $request = Craft::$app->getRequest();
                if (!$request->getIsConsoleRequest()) {
                    if ($request->getIsCpRequest()) {
                        if (!$user->can('accessCp')) {
                            return UserElement::AUTH_NO_CP_ACCESS;
                        }
                        if (
                            app()->isLive() === false &&
                            $user->can('accessCpWhenSystemIsOff') === false
                        ) {
                            return UserElement::AUTH_NO_CP_OFFLINE_ACCESS;
                        }
                    } elseif (
                        app()->isLive() === false &&
                        $user->can('accessSiteWhenSystemIsOff') === false
                    ) {
                        return UserElement::AUTH_NO_SITE_OFFLINE_ACCESS;
                    }
                }
        }

        return null;
    }

    /**
     * @param UserElement|null $user
     * @return string|null
     */
    public static function getAuthFailureMessage(?UserElement $user): ?string
    {
        switch ($user->authError ?? "") {
            case UserElement::AUTH_PENDING_VERIFICATION:
                return t('Account has not been activated.');
            case UserElement::AUTH_ACCOUNT_LOCKED:
                return t('Account locked.');
            case UserElement::AUTH_ACCOUNT_COOLDOWN:
                $timeRemaining = $user?->getRemainingCooldownTime();

                if ($timeRemaining) {
                    return t('Account locked. Try again in {time}.', ['time' => DateTimeHelper::humanDuration($timeRemaining)]);
                }
                return t('Account locked.');
            case UserElement::AUTH_PASSWORD_RESET_REQUIRED:
                return t('You need to reset your password.');
            case UserElement::AUTH_ACCOUNT_SUSPENDED:
                return t('Account suspended.');
            case UserElement::AUTH_NO_CP_ACCESS:
                return t('You cannot access the control panel with that account.');
            case UserElement::AUTH_NO_CP_OFFLINE_ACCESS:
                return t('You cannot access the control panel while the system is offline with that account.');
            case UserElement::AUTH_NO_SITE_OFFLINE_ACCESS:
                return t('You cannot access the site while the system is offline with that account.');
        }

        return null;
    }

    /**
     * @param string|null $authError
     * @param UserElement|null $user
     * @return array{0:string,1:string}
     * @since 5.8.10
     */
    public static function getLoginFailureInfo(?string $authError, ?UserElement $user): array
    {
        $generalConfig = Cms::config();
        // if preventUserEnumeration is true and the account is locked
        // set the $authError to a value that will trigger the generic, default message
        if (
            $generalConfig->preventUserEnumeration &&
            in_array($authError, [UserElement::AUTH_ACCOUNT_LOCKED, UserElement::AUTH_ACCOUNT_COOLDOWN])
        ) {
            $authError = UserElement::AUTH_INVALID_CREDENTIALS;
        }

        return [$authError, static::getLoginFailureMessage($authError, $user)];
    }

    /**
     * @param string|null $authError
     * @param UserElement|null $user
     * @return string
     * @deprecated in 5.8.10
     */
    public static function getLoginFailureMessage(?string $authError, ?UserElement $user): string
    {
        switch ($authError) {
            case UserElement::AUTH_PENDING_VERIFICATION:
                $message = t('Account has not been activated.');
                break;
            case UserElement::AUTH_ACCOUNT_LOCKED:
                $message = t('Account locked.');
                break;
            case UserElement::AUTH_ACCOUNT_COOLDOWN:
                $timeRemaining = $user?->getRemainingCooldownTime();

                if ($timeRemaining) {
                    $message = t('Account locked. Try again in {time}.', ['time' => DateTimeHelper::humanDuration($timeRemaining)]);
                } else {
                    $message = t('Account locked.');
                }
                break;
            case UserElement::AUTH_PASSWORD_RESET_REQUIRED:
                if (Craft::$app->getUsers()->sendPasswordResetEmail($user)) {
                    $message = t('You need to reset your password. Check your email for instructions.');
                } else {
                    $message = t('You need to reset your password, but an error was encountered when sending the password reset email.');
                }
                break;
            case UserElement::AUTH_ACCOUNT_SUSPENDED:
                $message = t('Account suspended.');
                break;
            case UserElement::AUTH_NO_CP_ACCESS:
                $message = t('You cannot access the control panel with that account.');
                break;
            case UserElement::AUTH_NO_CP_OFFLINE_ACCESS:
                $message = t('You cannot access the control panel while the system is offline with that account.');
                break;
            case UserElement::AUTH_NO_SITE_OFFLINE_ACCESS:
                $message = t('You cannot access the site while the system is offline with that account.');
                break;
            default:
                if (Cms::config()->useEmailAsUsername) {
                    $message = t('Invalid email or password.');
                } else {
                    $message = t('Invalid username or password.');
                }
        }

        return $message;
    }
}
