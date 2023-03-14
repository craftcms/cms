<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mfa\type;

use Base64Url\Base64Url;
use Craft;
use craft\elements\User;
use craft\helpers\Json;
use craft\mfa\ConfigurableMfaType;
use craft\mfa\webauthn\CredentialRepository;
use craft\records\WebAuthn as WebAuthnRecord;
use craft\web\twig\variables\Rebrand;
use craft\web\View;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource as CredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Server;

class WebAuthn extends ConfigurableMfaType
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
        return Craft::t('app', 'You can set up and manage your security keys from your account page.');
    }

    /**
     * @inheritdoc
     */
    public function isSetupForUser(User $user): bool
    {
        if (!Craft::$app->getRequest()->getIsSecureConnection()) {
            return false;
        }

        return $user->requireMfa && WebAuthnRecord::findOne(['userId' => $user->id]) !== null;
    }

    /**
     * @inheritdoc
     */
    public function getFields(): ?array
    {
        return [
            'credentialResponse' => '',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml(string $html = '', array $options = []): string
    {
        // TODO: write me
        return parent::getInputHtml($html, $options);
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

        // otherwise show instructions, QR code and verification form
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

        $html = Craft::$app->getView()->renderTemplate(
            '_components/mfa/webauthn/setup.twig',
            $data,
            View::TEMPLATE_MODE_CP
        );

        return parent::getSetupFormHtml($html, $withInto, $user);
    }

    public function removeSetup(): bool
    {
        // TODO: write me

        return true;
    }

    /**
     * Verify provided OTP (code)
     *
     * @param array $data
     * @return bool
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     */
    public function verify(array $data): bool
    {
        // TODO: write me

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
                static fn(CredentialSource $credential) => $credential->getPublicKeyCredentialDescriptor(),
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

    public function verifyRegistrationResponse(User $user, string $credentials): bool
    {
        $options = $this->getCredentialCreationOptions($user);

//        $verifiedCredentials = $this->getWebauthnServer()->loadAndCheckAttestationResponse(
//            Json::encode($credentials),
//            $options,
//            Craft::$app->getRequest()->asPsr7(),
//        );
        //$credentialRepository->saveNamedCredentialSource($credentials);
        return false;
    }

    public function getRelyingPartyEntity(): PublicKeyCredentialRpEntity
    {
        $data = [
            'name' => Craft::$app->getSystemName(),
            'id' => Craft::$app->getRequest()->getHostName(),
        ];

        // todo: uncomment once it's working
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

    public function getUserEntity(User $user): PublicKeyCredentialUserEntity
    {
        $data = [
            'name' => $user->username,
            'id' => Base64Url::encode($user->uid),
            'displayName' => $user->friendlyName,
        ];

        // todo: uncomment once it's working
//        $photo = $user->getPhoto();
//        if ($photo !== null) {
//            $data['icon'] = $photo->getDataUrl();
//        }

        return PublicKeyCredentialUserEntity::createFromArray($data);
    }
}
