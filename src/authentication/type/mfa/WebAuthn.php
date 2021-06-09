<?php
declare(strict_types=1);

namespace craft\authentication\type\mfa;

use Craft;
use craft\authentication\base\MfaType;
use craft\authentication\webauthn\CredentialRepository;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\models\AuthenticationState;
use craft\records\AuthWebAuthn;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource as CredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Server;

/**
 * This step type requires an authentication type that supports Web Authentication API.
 * This step type requires a user to be identified by a previous step.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 *
 * @property-read string $inputFieldHtml
 */
class WebAuthn extends MfaType
{
    /**
     * The key for session to use for storing the WebAuthn credential options.
     */
    public const WEBAUTHN_CREDENTIAL_OPTION_KEY = 'user.webauthn.credentialOptions';
    public const WEBAUTHN_CREDENTIAL_REQUEST_OPTION_KEY = 'user.webauthn.credentialRequestOptions';

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
        return ['credentialResponse'];
    }

    /**
     * @inheritdoc
     */
    public function authenticate(array $credentials, User $user = null): AuthenticationState
    {
        if (empty($credentials['credentialResponse']) || !$user) {
            return $this->state;
        }

        $credentialResponse = Json::encode($credentials['credentialResponse']);

        try {
            self::getWebauthnServer()->loadAndCheckAssertionResponse(
                $credentialResponse,
                Craft::$app->getSession()->get(self::WEBAUTHN_CREDENTIAL_REQUEST_OPTION_KEY),
                self::getUserEntity($user),
                Craft::$app->getRequest()->asPsr7()
            );
        } catch (\Throwable $exception) {
            Craft::$app->getErrorHandler()->logException($exception);
            return $this->state;
        }

        return $this->completeStep($user);
    }

    /**
     * @inheritdoc
     */
    public function getInputFieldHtml(): string
    {
        $server = self::getWebauthnServer();
        $userEntity = self::getUserEntity($this->state->getResolvedUser());
        $allowedCredentials = array_map(
            static fn(CredentialSource $credential) => $credential->getPublicKeyCredentialDescriptor(),
            (new CredentialRepository())->findAllForUserEntity($userEntity)
        );

        $requestOptions = $server->generatePublicKeyCredentialRequestOptions(null, $allowedCredentials);
        Craft::$app->getSession()->set(self::WEBAUTHN_CREDENTIAL_REQUEST_OPTION_KEY, $requestOptions);

        return Craft::$app->getView()->renderTemplate('_components/authenticationsteps/WebAuthn/input', [
            'requestOptions' => Json::encode($requestOptions),
        ]);
    }

    public static function getIsApplicable(User $user): bool
    {
        return Craft::$app->getRequest()->getIsSecureConnection() && AuthWebAuthn::findOne(['userId' => $user->id]);
    }

    /**
     * @inheritdoc
     */
    public static function hasUserSetup(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getUserSetupFormHtml(User $user): string
    {
        $existingCredentials = AuthWebAuthn::findAll(['userId' => $user->id]);

        $credentials = [];

        // TODO Models probably more appropriate than arrays?
        foreach ($existingCredentials as $existingCredential) {
            $credentials[] = ['name' => $existingCredential->name, 'dateLastUsed' => DateTimeHelper::toDateTime($existingCredential->dateLastUsed)];
        }

        return Craft::$app->getView()->renderTemplate('_components/authenticationsteps/WebAuthn/setup', [
            'credentialOptions' => Json::encode(self::getCredentialCreationOptions($user)),
            'existingCredentials' => $credentials
        ]);
    }

    /**
     * Return the WebAuthn server, responsible for key creation and validation.
     *
     * @return Server
     */
    public static function getWebauthnServer(): Server
    {
        return new Server(
            self::getRelayingPartyEntity(),
            new CredentialRepository()
        );
    }

    /**
     * Get the credential creation options.
     *
     * @param User $user The user for which to get the credential creation options.
     *
     * @return PublicKeyCredentialOptions | null
     */
    public static function getCredentialCreationOptions(User $user): ?PublicKeyCredentialOptions
    {
        if (Craft::$app->getEdition() !== Craft::Pro) {
            return null;
        }

        $session = Craft::$app->getSession();
        $credentialOptions = $session->get(self::WEBAUTHN_CREDENTIAL_OPTION_KEY);

        if (!$credentialOptions) {
            $userEntity = self::getUserEntity($user);

            $excludeCredentials = array_map(
                static fn (CredentialSource $credential) => $credential->getPublicKeyCredentialDescriptor(),
                (new CredentialRepository())->findAllForUserEntity($userEntity)
            );

            $credentialOptions = Json::encode(
                self::getWebauthnServer()->generatePublicKeyCredentialCreationOptions(
                    $userEntity,
                    null,
                    $excludeCredentials
                )
            );

            $session->set(self::WEBAUTHN_CREDENTIAL_OPTION_KEY, $credentialOptions);
        }

        return PublicKeyCredentialCreationOptions::createFromArray(Json::decodeIfJson($credentialOptions));
    }

    /**
     * Return a new Public Key Credential User Entity based on the currently logged in user.
     *
     * @param User $user
     * @return PublicKeyCredentialUserEntity
     */
    public static function getUserEntity(User $user): PublicKeyCredentialUserEntity
    {
        return new PublicKeyCredentialUserEntity($user->username, $user->uid, $user->friendlyName);
    }

    /**
     * Return a new Public Key Credential Relaying Party Entity based on the current Craft installations
     *
     * @return PublicKeyCredentialRpEntity
     */
    public static function getRelayingPartyEntity(): PublicKeyCredentialRpEntity
    {
        return new PublicKeyCredentialRpEntity(Craft::$app->getSites()->getPrimarySite()->getName(), Craft::$app->getRequest()->getHostName());
    }
}
