<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\type;

use Base64Url\Base64Url;
use Craft;
use craft\auth\Configurable2faType;
use craft\auth\webauthn\CredentialRepository;
use craft\elements\User;
use craft\helpers\Json;
use craft\records\WebAuthn as WebAuthnRecord;
use craft\web\twig\variables\Rebrand;
use craft\web\View;
use GuzzleHttp\Psr7\ServerRequest;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Server;

class WebAuthn extends Configurable2faType
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
        return Craft::t('app', 'Faster login with WebAuthn (e.g. TouchID, FaceID and Yubikey). You can set up and manage your security keys from your account page.');
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

        // otherwise show keys table and setup button
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

    /**
     * Return the WebAuthn server, responsible for key creation and validation.
     *
     * @return Server
     */
    public function getWebauthnServer(): Server
    {
        return Craft::createObject(Server::class, [
            $this->getRelyingPartyEntity(),
            Craft::createObject(CredentialRepository::class),
        ]);
    }

    /**
     * Get the credential creation options.
     *
     * @param User $user The user for which to get the credential creation options.
     * @param bool $createNew Whether new credential options should be created
     *
     * @return PublicKeyCredentialOptions | null
     */
    public function getCredentialCreationOptions(User $user, bool $createNew = false): ?PublicKeyCredentialOptions
    {
        if (Craft::$app->getEdition() !== Craft::Pro) {
            return null;
        }

        $session = Craft::$app->getSession();
        $credentialOptions = $session->get(self::WEBAUTHN_CREDENTIAL_OPTIONS_KEY);

        if ($createNew || !$credentialOptions) {
            $userEntity = $this->getUserEntity($user);

            $excludeCredentials = array_map(
                static fn(PublicKeyCredentialSource $credential) => $credential->getPublicKeyCredentialDescriptor(),
                Craft::createObject(CredentialRepository::class)->findAllForUserEntity($userEntity));

            $credentialOptions = Json::encode(
                $this->getWebauthnServer()->generatePublicKeyCredentialCreationOptions(
                    $userEntity,
                    PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
                    $excludeCredentials,
                )
            );

            $session->set(self::WEBAUTHN_CREDENTIAL_OPTIONS_KEY, $credentialOptions);
        }

        return PublicKeyCredentialCreationOptions::createFromArray(Json::decodeIfJson($credentialOptions));
    }

    /**
     * Verify WebAuthn registration response & save to DB
     *
     * @param User $user
     * @param string $credentials
     * @param ?string $credentialName
     * @return bool
     */
    public function verifyRegistrationResponse(User $user, string $credentials, ?string $credentialName = null): bool
    {
        $options = $this->getCredentialCreationOptions($user);
        
        $psrServerRequest = $this->getPsrServerRequest();

        try {
            $verifiedCredentials = $this->getWebauthnServer()->loadAndCheckAttestationResponse(
                $credentials,
                /** @phpstan-ignore-next-line */
                $options,
                $psrServerRequest,
            );
        } catch (\Exception) {
            return false;
        }

        $credentialRepository = new CredentialRepository();
        $credentialRepository->savedNamedCredentialSource($verifiedCredentials, $credentialName);

        return true;
    }



    /**
     * Get the credential creation options.
     *
     * @param User $user The user for which to get the credential request options.
     *
     * @return PublicKeyCredentialOptions | null
     */
    public function generateCredentialRequestOptions(User $user): ?PublicKeyCredentialOptions
    {
        if (Craft::$app->getEdition() !== Craft::Pro) {
            return null;
        }

        $server = $this->getWebauthnServer();
        $userEntity = $this->getUserEntity($user);
        $allowedCredentials = array_map(
            static fn(PublicKeyCredentialSource $credential) => $credential->getPublicKeyCredentialDescriptor(),
            Craft::createObject(CredentialRepository::class)->findAllForUserEntity($userEntity));

        return $server->generatePublicKeyCredentialRequestOptions(null, $allowedCredentials);
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
        $psrServerRequest = $this->getPsrServerRequest();

        try {
            $this->getWebauthnServer()->loadAndCheckAssertionResponse(
                $credentials,
                $authenticationOptions,
                $this->getUserEntity($user),
                $psrServerRequest,
            );
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function getRelyingPartyEntity(): PublicKeyCredentialRpEntity
    {
        $data = [
            'name' => Craft::$app->getSystemName(),
            'id' => Craft::$app->getRequest()->getHostName(),
        ];

//        $rebrand = new Rebrand();
//        if (Craft::$app->getEdition() === Craft::Pro && ($rebrand->isIconUploaded() || $rebrand->isLogoUploaded())) {
//            if ($rebrand->isIconUploaded()) {
//                $data['icon'] = $rebrand->getIcon()?->getDataUrl();
//            } elseif ($rebrand->isLogoUploaded()) {
//                $data['icon'] = $rebrand->getLogo()?->getDataUrl();
//            }
//        }

        return PublicKeyCredentialRpEntity::createFromArray($data);
    }

    /**
     * Get PublicKeyCredentialUserEntity based on User
     *
     * @param User $user
     * @return PublicKeyCredentialUserEntity
     */
    public function getUserEntity(User $user): PublicKeyCredentialUserEntity
    {
        $data = [
            'name' => $user->username,
            'id' => Base64Url::encode($user->uid),
            'displayName' => $user->friendlyName,
        ];

//        $photo = $user->getPhoto();
//        if ($photo !== null) {
//            $data['icon'] = $photo->getDataUrl();
//        }

        return PublicKeyCredentialUserEntity::createFromArray($data);
    }

    /**
     * Get server request in a format that WebAuthn expects
     *
     * @return ServerRequest
     */
    protected function getPsrServerRequest(): ServerRequest
    {
        $request = Craft::$app->getRequest();

        return new ServerRequest(
            $request->getMethod(),
            $request->getFullUri(),
            $request->getHeaders()->toArray(),
            $request->getRawBody()
        );
    }
}
