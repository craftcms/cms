<?php
declare(strict_types=1);

namespace craft\authentication\type\mfa;

use Craft;
use craft\authentication\base\MfaType;
use craft\elements\User;
use craft\helpers\Authentication as AuthenticationHelper;
use craft\models\AuthenticationState;

/**
 * This step type requires the user to enter TOTP
 * This step type requires a user to be identified by a previous step.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthenticatorCode extends MfaType
{
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
    public function authenticate(array $credentials, User $user = null): AuthenticationState
    {
        if (is_null($user) || empty($credentials['verification-code'])) {
            return $this->state;
        }

        $code = $credentials['verification-code'];
        $session = Craft::$app->getSession();

        if (empty($code) || !$user->verifyAuthenticatorKey($code)) {
            $session->setError(Craft::t('app', 'The verification code is incorrect.'));
            return $this->state;
        }

        return $this->completeStep($user);
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
    public static function getIsApplicable(User $user): bool
    {
        return $user->hasAuthenticatorSecret();
    }

    public static function hasUserSetup(): bool
    {
        return true;
    }

    public function getUserSetupFormHtml(User $user): string
    {

        $qrAuthenticatorCode = '';

        if (Craft::$app->getEdition() == Craft::Pro && $user->getIsCurrent() && !$user->hasAuthenticatorSecret()) {
            $session = Craft::$app->getSession();
            $existingSecret = $session->get(AuthenticationHelper::AUTHENTICATOR_SECRET_SESSION_KEY);

            $codeAuthenticator = AuthenticationHelper::getCodeAuthenticator();

            if (!$existingSecret) {
                $existingSecret = $codeAuthenticator->generateSecretKey(32);
                $session->set(AuthenticationHelper::AUTHENTICATOR_SECRET_SESSION_KEY, $existingSecret);
            }

            $qrAuthenticatorCode = $codeAuthenticator->getQRCodeInline(
                Craft::$app->getSites()->getPrimarySite()->getName(),
                $user->email,
                $existingSecret
            );
        }


        return Craft::$app->getView()->renderTemplate('_components/authenticationsteps/AuthenticatorCode/setup', [
            'user' => $user,
            'qrAuthenticatorCode' => $qrAuthenticatorCode
        ]);
    }
}
