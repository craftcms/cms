<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\elements\User as UserElement;

/**
 * Class User
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.7
 */
class User
{
    /**
     * @param string|null $authError
     * @param UserElement|null $user
     * @return string
     */
    public static function getLoginFailureMessage(?string $authError, ?UserElement $user): string
    {
        switch ($authError) {
            case UserElement::AUTH_PENDING_VERIFICATION:
                $message = Craft::t('app', 'Account has not been activated.');
                break;
            case UserElement::AUTH_ACCOUNT_LOCKED:
                $message = Craft::t('app', 'Account locked.');
                break;
            case UserElement::AUTH_ACCOUNT_COOLDOWN:
                $timeRemaining = $user?->getRemainingCooldownTime();

                if ($timeRemaining) {
                    $message = Craft::t('app', 'Account locked. Try again in {time}.', ['time' => DateTimeHelper::humanDuration($timeRemaining)]);
                } else {
                    $message = Craft::t('app', 'Account locked.');
                }
                break;
            case UserElement::AUTH_PASSWORD_RESET_REQUIRED:
                if (Craft::$app->getUsers()->sendPasswordResetEmail($user)) {
                    $message = Craft::t('app', 'You need to reset your password. Check your email for instructions.');
                } else {
                    $message = Craft::t('app', 'You need to reset your password, but an error was encountered when sending the password reset email.');
                }
                break;
            case UserElement::AUTH_ACCOUNT_SUSPENDED:
                $message = Craft::t('app', 'Account suspended.');
                break;
            case UserElement::AUTH_NO_CP_ACCESS:
                $message = Craft::t('app', 'You cannot access the control panel with that account.');
                break;
            case UserElement::AUTH_NO_CP_OFFLINE_ACCESS:
                $message = Craft::t('app', 'You cannot access the control panel while the system is offline with that account.');
                break;
            case UserElement::AUTH_NO_SITE_OFFLINE_ACCESS:
                $message = Craft::t('app', 'You cannot access the site while the system is offline with that account.');
                break;
            default:
                if (Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
                    $message = Craft::t('app', 'Invalid email or password.');
                } else {
                    $message = Craft::t('app', 'Invalid username or password.');
                }
        }

        return $message;
    }
}
