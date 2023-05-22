<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\type;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Craft;
use craft\auth\ConfigurableAuthType;
use craft\elements\User;
use craft\records\Authenticator as AuthenticatorRecord;
use craft\web\View;
use PragmaRX\Google2FA\Google2FA;

class GoogleAuthenticator extends ConfigurableAuthType
{
    /**
     * The key to store the authenticator secret in session, while setting up this method.
     */
    protected const AUTHENTICATOR_SECRET_SESSION_KEY = 'craft.authenticator.secret';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Google Authenticator');
    }

    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return Craft::t('app', 'Authenticate via single use code provided by a third-party application like Google Authenticator.');
    }

    /**
     * @inheritdoc
     */
    public function isSetupForUser(User $user): bool
    {
        return self::_getSecretFromDb($user->id) !== null;
    }

    /**
     * @inheritdoc
     */
    public function getFields(): ?array
    {
        return [
            'verificationCode' => Craft::t('app', 'Verification code'),
        ];
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

        // if secret is stored in the DB - show the verification code form only (it means they've finished the setup)
        if (self::_getSecretFromDb($user->id)) {
            $view = Craft::$app->getView();
            $view->templateMode = View::TEMPLATE_MODE_CP;
            $formHtml = Craft::$app->getView()->renderTemplate(
                '_components/auth/googleauthenticator/verification.twig',
                $data
            );
        } else {
            // otherwise show the setup form (instructions, QR code and verification input(s))
            $formHtml = $this->getSetupFormHtml('',false, $user);
        }

        return parent::getInputHtml($formHtml, $options);
    }

    /**
     * @inheritdoc
     */
    public function getSetupFormHtml(string $html = '', bool $withInto = false, ?User $user = null): string
    {
        if ($user === null) {
            $user = Craft::$app->getAuth()->getUserFor2fa();
        }

        if ($user === null) {
            return '';
        }

        // otherwise show instructions, QR code and verification form
        $data = [
            'secret' => $this->getSecret($user),
            'user' => $user,
            'fields' => $this->getNamespacedFields(),
            'withIntro' => $withInto,
            'currentMethod' => self::class,
        ];
        $data['qrCode'] = $this->generateQrCode($user, $data['secret']);

        if ($withInto) {
            $data['typeName'] = self::displayName();
            $data['typeDescription'] = self::getDescription();
        }

        $html = Craft::$app->getView()->renderTemplate(
            '_components/auth/googleauthenticator/setup.twig',
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
        $userId = Craft::$app->getUser()->getId();

        if ($userId === null) {
            return false;
        }

        AuthenticatorRecord::deleteAll(['userId' => $userId]);

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
        $user = Craft::$app->getAuth()->getUserFor2fa();

        if ($user === null) {
            return false;
        }

        // check if secret is stored, if not, we need to store it
        $storedSecret = self::_getSecretFromDb($user->id);
        $session = Craft::$app->getSession();

        if ($storedSecret === null) {
            $secret = $session->get(self::AUTHENTICATOR_SECRET_SESSION_KEY);
        } else {
            $secret = $storedSecret;
        }

        if ($secret === null) {
            return false;
        }

        $code = $data['verificationCode'];
        if (empty($code)) {
            return false;
        }

        // verify the code:
        $verified = (new Google2FA())->verifyKey($secret, $code);

        if ($verified && $storedSecret === null) {
            $this->_storeSecretInDb($user->id, $secret);
            $session->remove(self::AUTHENTICATOR_SECRET_SESSION_KEY);
        }

        return $verified;
    }


    // GoogleAuthenticator-specific methods
    // -------------------------------------------------------------------------

    /**
     * Get 2FA secret key. If one doesn't exist, generate and store it in the DB.
     *
     * @param User $user
     * @return string
     */
    public function getSecret(User $user): string
    {
        $google2fa = new Google2FA();
        $secret = self::_getSecretFromDb($user->id);

        if (empty($secret)) {
            try {
                $secret = $google2fa->generateSecretKey(32);
                Craft::$app->getSession()->set(self::AUTHENTICATOR_SECRET_SESSION_KEY, $secret);
            } catch (\Exception $e) {
                Craft::$app->getErrorHandler()->logException($e);
            }
        }

        return chunk_split($secret, 4, ' ');
    }

    /**
     * Generate the QR code for initial setup of this 2FA method
     *
     * @param User $user
     * @return string
     */
    public function generateQrCode(User $user, string $secret): string
    {
        $qrCodeUrl = (new Google2FA())->getQRCodeUrl(
            Craft::$app->getSystemName(),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($qrCodeUrl);
    }

    /**
     * Return user's 2FA secret from the database.
     *
     * @param int $userId
     * @return string|null
     */
    private static function _getSecretFromDb(int $userId): ?string
    {
        $record = AuthenticatorRecord::find()
            ->select(['auth2faSecret'])
            ->where(['userId' => $userId])
            ->one();

        return $record ? $record['auth2faSecret'] : null;
    }

    /**
     * Store obtained 2FA secret in the DB against userId
     *
     * @param int $userId
     * @param string $secret
     * @return void
     */
    private function _storeSecretInDb(int $userId, string $secret): void
    {
        $record = AuthenticatorRecord::find()
            ->where(['userId' => $userId])
            ->one();

        if (!$record) {
            $record = new AuthenticatorRecord();
            $record->userId = $userId;
        }

        /** @var AuthenticatorRecord $record */
        $record->auth2faSecret = $secret;
        $record->save();
    }
}
