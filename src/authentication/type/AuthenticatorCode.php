<?php
declare(strict_types=1);

namespace craft\authentication\type;

use Craft;
use craft\authentication\base\ElevatedSessionTypeInterface;
use craft\authentication\base\MfaTypeInterface;
use craft\authentication\base\Type;
use craft\authentication\base\UserConfigurableTypeInterface;
use craft\elements\User;
use craft\helpers\Authentication as AuthenticationHelper;
use craft\authentication\State;

/**
 * This step type requires the user to enter TOTP
 * This step type requires a user to be identified by a previous step.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 *
 * @property-read string $inputFieldHtml
 */
class AuthenticatorCode extends Type implements MfaTypeInterface, UserConfigurableTypeInterface, ElevatedSessionTypeInterface
{
    /**
     * The key to store the authenticator secret in session, while attaching it.
     */
    public const AUTHENTICATOR_SECRET_SESSION_KEY = 'user.authenticator.secret';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Authenticate with Google Authenticator');
    }

    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return Craft::t('app', 'Enter an authentication code provided by Google Authenticator.');
    }

    /**
     * @inheritdoc
     */
    public function getFields(): ?array
    {
        return ['verification-code'];
    }

    /**
     * @inheritdoc
     */
    public function authenticate(array $credentials, User $user = null): bool
    {
        if (is_null($user) || empty($credentials['verification-code'])) {
            return false;
        }

        $code = $credentials['verification-code'];
        $session = Craft::$app->getSession();

        if (empty($code)) {
            $session->setError(Craft::t('app', 'Please enter a verification code.'));
            return false;
        }

        if (is_numeric($code)) {
            if (!$user->verifyAuthenticatorCode($code)) {
                $session->setError(Craft::t('app', 'The verification code is incorrect.'));
                return false;
            }

            return true;
        }

        // Not empty and not numeric. What if it's a backup code?
        if (!$user->useAuthenticatorBackupCode($code)) {
            $session->setError(Craft::t('app', 'The verification code is incorrect.'));
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getInputFieldHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/authenticationsteps/AuthenticatorCode/input');
    }

    /**
     * @inheritdoc
     */
    public static function getIsApplicable(?User $user): bool
    {
        return $user && $user->hasAuthenticatorSecret();
    }

    public function getUserSetupFormHtml(User $user): string
    {

        $qrAuthenticatorCode = '';

        if ($user->getIsCurrent() && !$user->hasAuthenticatorSecret()) {
            $session = Craft::$app->getSession();
            $existingSecret = $session->get(self::AUTHENTICATOR_SECRET_SESSION_KEY);

            $codeAuthenticator = AuthenticationHelper::getCodeAuthenticator();

            if (!$existingSecret) {
                $existingSecret = $codeAuthenticator->generateSecretKey(32);
                $session->set(self::AUTHENTICATOR_SECRET_SESSION_KEY, $existingSecret);
            }

            $qrAuthenticatorCode = $codeAuthenticator->getQRCodeInline(
                Craft::$app->getSites()->getPrimarySite()->getName(),
                $user->email,
                $existingSecret
            );
        }

        $isSecureConnection = Craft::$app->getRequest()->getIsSecureConnection();

        return Craft::$app->getView()->renderTemplate('_components/authenticationsteps/AuthenticatorCode/setup',
            compact('user', 'qrAuthenticatorCode', 'isSecureConnection'));
    }

    /**
     * @inheritdoc
     */
    public static function getHasUserSetup(): bool
    {
       return true;
    }

    /**
     * @inheritdoc
     */
    public static function isAvailableForUser(User $user): bool
    {
        return $user->hasAuthenticatorSecret();
    }

    /**
     * Hash a backup code for comparison.
     *
     * @param string $backupCode
     * @param string $authSecret
     * @return string
     */
    public static function hashBackupCode(string $backupCode, string $authSecret): string {
        return sha1($authSecret . $backupCode);
    }
}
