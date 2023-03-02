<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mfa\type;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Craft;
use craft\base\mfa\BaseMfaType;
use craft\elements\User;
use craft\mfa\ConfigurableMfaInterface;
use craft\records\Authenticator as AuthenticatorRecord;
use PragmaRX\Google2FA\Google2FA;

class GoogleAuthenticator extends BaseMfaType implements ConfigurableMfaInterface
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
        return Craft::t('app', 'Authenticate via single use code provided by a third-party application line Google Authenticator.');
    }

    /**
     * @inheritdoc
     */
    public static function isSetupForUser(User $user): bool
    {
        return $user->requireMfa && self::_getSecretFromDb($user->id) !== null;
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
    public function getFormHtml(User $user, string $html = '', ?array $options = []): string
    {
        $data = [
            'user' => $user,
            'fields' => $this->getFields(),
        ];

        // if secret is stored in the DB - show the verification code form only (it means they've finished the setup)
        if (self::_getSecretFromDb($user->id)) {
            $formHtml = Craft::$app->getView()->renderTemplate(
                '_components/mfa/googleauthenticator/verification.twig',
                $data
            );
        } else {
            // otherwise show instructions, QR code and verification form
            $data['secret'] = $this->getSecret($user);
            $data['qrCode'] = $this->generateQrCode($user, $data['secret']);

            $formHtml = Craft::$app->getView()->renderTemplate(
                '_components/mfa/googleauthenticator/setup.twig',
                $data + ['showEnableCheckbox' => false]
            );
        }

        return parent::getFormHtml($user, $formHtml, $options);
    }

    /**
     * Verify provided OTP (code)
     *
     * @param User $user
     * @param array $data
     * @return bool
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     */
    public function verify(User $user, array $data): bool
    {
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
     * Get MFA secret key. If one doesn't exist, generate and store it in the DB.
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
                $secret = $google2fa->generateSecretKey(); //todo: change to (32)
                Craft::$app->getSession()->set(self::AUTHENTICATOR_SECRET_SESSION_KEY, $secret);
            } catch (\Exception $e) {
                // todo: log in a new log file????
            }
        }

        return chunk_split($secret, 4, ' ');
    }

    /**
     * Generate the QR code for initial setup of this MFA method
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
     * Return user's MFA secret from the database.
     *
     * @param int $userId
     * @return string|null
     */
    private static function _getSecretFromDb(int $userId): ?string
    {
        $record = AuthenticatorRecord::find()
            ->select(['mfaSecret'])
            ->where(['userId' => $userId])
            ->one();

        return $record ? $record['mfaSecret'] : null;
    }

    /**
     * Store obtained MFA secret in the DB against userId
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
        $record->mfaSecret = $secret;
        $record->save();
    }
}
