<?php
declare(strict_types=1);

namespace craft\authentication\type\mfa;

use Craft;
use craft\authentication\base\MfaType;
use craft\authentication\base\Type;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\mail\Message;
use craft\models\AuthenticationState;

/**
 * This step type requires an authentication type that supports Web Authentication API.
 * This step type requires a user to be identified by a previous step.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class WebAuthn extends MfaType
{
    /**
     * The key to store the WebAuthn challenge in session.
     */
    public const WEBAUTHN_CHALLENGE_SESSION_KEY = 'user.webauthn.challenge';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Authenticate with WebAuthn');
    }

    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return Craft::t('app', 'Authenticate using a Yubikey or TouchID.');
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
    public function prepareForAuthentication(User $user = null): void
    {

    }

    /**
     * @inheritdoc
     */
    public function authenticate(array $credentials, User $user = null): AuthenticationState
    {

        return $this->completeStep($user);
    }

    /**
     * @inheritdoc
     */
    public function getInputFieldHtml(): string
    {
        return 'fields';
    }

    public static function getIsApplicable(User $user): bool
    {
        return false;
    }

    public static function hasUserSetup(): bool
    {
        return true;
    }

    public function getUserSetupFormHtml(User $user): string
    {
        $challenge = '';

        // TODO check for existing webauthn credentials
        if (Craft::$app->getEdition() == Craft::Pro && $user->getIsCurrent()) {
            $session = Craft::$app->getSession();
            $challenge = $session->get(self::WEBAUTHN_CHALLENGE_SESSION_KEY);
            if (!$challenge) {
                $challenge = StringHelper::randomString(32);
                $session->set(self::WEBAUTHN_CHALLENGE_SESSION_KEY, $challenge);
            }

        }
        return Craft::$app->getView()->renderTemplate('_components/authenticationsteps/WebAuthn/setup', [
            'user' => $user,
            'challenge' => $challenge,
            'primarySiteName' => Craft::$app->getSites()->getPrimarySite()->getName()
        ]);

    }
}
