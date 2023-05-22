<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\type;

use Base64Url\Base64Url;
use Craft;
use craft\auth\ConfigurableAuthType;
use craft\auth\webauthn\CredentialRepository;
use craft\elements\User;
use craft\helpers\Json;
use craft\records\WebAuthn as WebAuthnRecord;
use craft\web\View;
use GuzzleHttp\Psr7\ServerRequest;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Server;

class WebAuthn extends ConfigurableAuthType
{
    /**
     * The key for session to use for storing the WebAuthn credential options.
     */
    public const WEBAUTHN_CREDENTIAL_OPTIONS_KEY = 'user.webauthn.credentialOptions';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Authenticate with a security key');
    }

    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return Craft::t('app', 'Faster login with WebAuthn (e.g. TouchID, FaceID, Windows Hello, fingerprint). You can set up and manage your security keys from your account page.');
    }

    /**
     * @inheritdoc
     */
    public function isSetupForUser(User $user): bool
    {
        if (!Craft::$app->getRequest()->getIsSecureConnection()) {
            return false;
        }

        return WebAuthnRecord::findOne(['userId' => $user->id]) !== null;
    }

    /**
     * @inheritdoc
     */
    public function getFields(): ?array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml(string $html = '', array $options = []): string
    {
        $user = Craft::$app->getAuth()->getUserFor2fa();

        if ($user === null) {
            return '';
        }

        $data = [
            'user' => $user,
            'fields' => $this->getNamespacedFields(),
            'currentMethod' => self::class,
        ];

        $view = Craft::$app->getView();
        $view->templateMode = View::TEMPLATE_MODE_CP;
        $formHtml = Craft::$app->getView()->renderTemplate(
            '_components/auth/webauthn/verification.twig',
            $data
        );

        return parent::getInputHtml($formHtml, $options);
    }

    /**
     * @inheritdoc
     */
    public function getSetupFormHtml(string $html = '', bool $withInto = false, ?User $user = null): string
    {
        if ($user === null) {
            $user = Craft::$app->getUser()->getIdentity();
        }

        if ($user === null) {
            return '';
        }

        // show keys table and setup button
        $data = [
            'user' => $user,
            'fields' => $this->getNamespacedFields(),
            'withIntro' => $withInto,
            'currentMethod' => self::class,
        ];

        if ($withInto) {
            $data['typeName'] = self::displayName();
            $data['typeDescription'] = self::getDescription();
        }

        $credentials = WebAuthnRecord::find()
            ->select(['credentialName', 'dateLastUsed', 'uid'])
            ->where(['userId' => $user->id])
            ->all();

        $data['credentials'] = $credentials;

        $html = Craft::$app->getView()->renderTemplate(
            '_components/auth/webauthn/setup.twig',
            $data,
            View::TEMPLATE_MODE_CP
        );

        return parent::getSetupFormHtml($html, $withInto, $user);
    }

    /**
     * @inheritdoc
     */
    public function removeSetup(): bool
    {
        return true;
    }

    /**
     * Delete WebAuthn Security key by UID
     *
     * @param string $uid
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteSecurityKey(User $user, string $uid): bool
    {
        return WebAuthnRecord::findOne(['userId' => $user->id, 'uid' => $uid])?->delete();
    }

    /**
     * @inheritdoc
     *
     * @param array $data
     * @return bool
     */
    public function verify(array $data): bool
    {
        return false;
    }

    // WebAuthn-specific methods
    // -------------------------------------------------------------------------

    /**
     * Get the credential creation options.
     *
     * @param User $user The user for which to get the credential creation options.
     * @param bool $createNew Whether new credential options should be created
     * @return PublicKeyCredentialOptions|null
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\InvalidConfigException
     */
    public function getCredentialCreationOptions(User $user, bool $createNew = false): ?PublicKeyCredentialOptions
    {
        $session = Craft::$app->getSession();
        $credentialOptions = $session->get(self::WEBAUTHN_CREDENTIAL_OPTIONS_KEY);

        if ($createNew || !$credentialOptions) {
            $userEntity = $this->_getUserEntity($user);

            $excludeCredentials = array_map(
                static fn(PublicKeyCredentialSource $credential) => $credential->getPublicKeyCredentialDescriptor(),
                Craft::createObject(CredentialRepository::class)->findAllForUserEntity($userEntity));


            $credentialOptions = Json::encode(
                $this->_getWebauthnServer()->generatePublicKeyCredentialCreationOptions(
                    $userEntity,
                    PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
                    $excludeCredentials,
                    $this->_getAuthenticatorSelectionCriteria(),
                )
            );

            $session->set(self::WEBAUTHN_CREDENTIAL_OPTIONS_KEY, $credentialOptions);
        }

        return PublicKeyCredentialCreationOptions::createFromArray(Json::decodeIfJson($credentialOptions));
    }

    /**
     * Verify WebAuthn registration response and save to DB
     *
     * @param User $user
     * @param string $credentials
     * @param string|null $credentialName
     * @return bool
     * @throws \Throwable
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\InvalidConfigException
     */
    public function verifyRegistrationResponse(User $user, string $credentials, ?string $credentialName = null): bool
    {
        /** @var PublicKeyCredentialCreationOptions $options */
        $options = $this->getCredentialCreationOptions($user);

        try {
            $verifiedCredentials = $this->_getWebauthnServer()->loadAndCheckAttestationResponse(
                $credentials,
                $options,
                $this->_getPsrServerRequest(),
            );
        } catch (\Exception) {
            return false;
        }

        $credentialRepository = new CredentialRepository();
        $credentialRepository->savedNamedCredentialSource($verifiedCredentials, $credentialName);

        return true;
    }

    /**
     * Get the credential request options.
     *
     * @param string|bool $usernameless
     * @return PublicKeyCredentialOptions | null
     */
    public function getCredentialRequestOptions(string|bool $usernameless = false): ?PublicKeyCredentialOptions
    {
        // if we're doing usernameless authentication
        // proceed with userVerification: preferred and empty allowed credentials
        if ($usernameless === true) {
            return $this->_getWebauthnServer()->generatePublicKeyCredentialRequestOptions(
                PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
                []
            );
        }

        // otherwise - get a user
        if (is_string($usernameless)) {
            // if a string was passed - that's username or email; used by e.g. AuthManager.js
            $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($usernameless);
        } else {
            // if it wasn't a string, get user from session; use by e.g. ElevatedSessionManager.js
            $user = Craft::$app->getUser()->getIdentity();
        }
        if ($user === null) {
            return null;
        }

        $userEntity = $this->_getUserEntity($user);
        // and get a list of allowed credentials for given user
        $allowedCredentials = array_map(
            static fn(PublicKeyCredentialSource $credential) => $credential->getPublicKeyCredentialDescriptor(),
            Craft::createObject(CredentialRepository::class)->findAllForUserEntity($userEntity));

        return $this->_getWebauthnServer()->generatePublicKeyCredentialRequestOptions(null, $allowedCredentials);
    }

    /**
     * Verify WebAuthn authentication response
     *
     * @param User $user
     * @param PublicKeyCredentialRequestOptions $authenticationOptions
     * @param string $credentials
     * @return bool
     */
    public function verifyAuthenticationResponse(User $user, PublicKeyCredentialRequestOptions $authenticationOptions, string $credentials): bool
    {
        $psrServerRequest = $this->_getPsrServerRequest();

        try {
            $this->_getWebauthnServer()->loadAndCheckAssertionResponse(
                $credentials,
                $authenticationOptions,
                $this->_getUserEntity($user),
                $psrServerRequest,
            );
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Get PublicKeyCredentialUserEntity based on User
     *
     * @param User $user
     * @return PublicKeyCredentialUserEntity
     */
    private function _getUserEntity(User $user): PublicKeyCredentialUserEntity
    {
        $data = [
            'name' => $user->email,
            'id' => Base64Url::encode($user->uid),
            'displayName' => $user->friendlyName,
        ];

        return PublicKeyCredentialUserEntity::createFromArray($data);
    }

    /**
     * Get server request in a format that WebAuthn expects
     *
     * @return ServerRequest
     */
    private function _getPsrServerRequest(): ServerRequest
    {
        $request = Craft::$app->getRequest();

        return new ServerRequest(
            $request->getMethod(),
            $request->getFullUri(),
            $request->getHeaders()->toArray(),
            $request->getRawBody()
        );
    }

    /**
     * Return the WebAuthn server, responsible for key creation and validation.
     *
     * @return Server
     * @throws \yii\base\InvalidConfigException
     */
    private function _getWebauthnServer(): Server
    {
        return Craft::createObject(Server::class, [
            $this->_getRelyingPartyEntity(),
            Craft::createObject(CredentialRepository::class),
        ]);
    }

    /**
     * Get relying party entity (rp)
     *
     * @return PublicKeyCredentialRpEntity
     */
    public function _getRelyingPartyEntity(): PublicKeyCredentialRpEntity
    {
        $data = [
            'name' => Craft::$app->getSystemName(),
            'id' => Craft::$app->getRequest()->getHostName(),
        ];

        return PublicKeyCredentialRpEntity::createFromArray($data);
    }

    /**
     * Get authenticator selection criteria to allow usernameless login
     * https://github.com/MasterKale/SimpleWebAuthn/issues/96#issuecomment-771137312
     *
     * @return AuthenticatorSelectionCriteria
     */
    private function _getAuthenticatorSelectionCriteria(): AuthenticatorSelectionCriteria
    {
        $authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria();
        $authenticatorSelectionCriteria->setRequireResidentKey(true);
        $authenticatorSelectionCriteria->setUserVerification(AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED);

        return $authenticatorSelectionCriteria;
    }
}
