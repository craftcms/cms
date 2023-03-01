<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\authentication\type;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Craft;
use craft\base\authentication\BaseAuthenticationType;
use craft\elements\User;
use craft\records\Authenticator as AuthenticatorRecord;
use PragmaRX\Google2FA\Google2FA;

class GoogleAuthenticator extends BaseAuthenticationType
{
    /**
     * @var string|null
     */
    private ?string $_secret = null;

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
    public function getFields(): ?array
    {
        return [
            'verificationCode' => Craft::t('app', 'Verification code'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getFormHtml(User $user): string
    {
        $data = [
            'user' => $user,
            'fields' => $this->getFields(),
        ];

        // if secret is stored in the DB - show the verification code form only (it means they've finished the setup)
        if ($this->_getStoredSecret($user->id)) {
            $html = Craft::$app->getView()->renderTemplate(
                '_components/authentication/googleauthenticator/verification.twig',
                $data
            );
        } else {
            // otherwise show instructions, QR code and verification form
            $data['secret'] = $this->getSecret($user);
            $data['qrCode'] = $this->generateQrCode($user);

            $html = Craft::$app->getView()->renderTemplate(
                '_components/authentication/googleauthenticator/setup.twig',
                $data + ['showEnableCheckbox' => false]
            );
        }

        return $html;
    }

    /**
     * Get MFA secret key. If one doesn't exist, generate and store it in the DB.
     *
     * @param User $user
     * @return string
     */
    public function getSecret(User $user): string
    {
        $google2fa = new Google2FA();
        $secret = $this->_getStoredSecret($user->id);

        if (empty($secret)) {
            try {
                $secret = $google2fa->generateSecretKey();
                //$this->_storeSecret($user->id, $secret);
            } catch (\Exception $e) {
                // todo: log in a new log file????
                $response['success'] = false;
            }
        }

        $this->_secret = $secret;

        return chunk_split($secret, 4, ' ');
    }

    /**
     * Generate the QR code for initial setup of this MFA method
     *
     * @param User $user
     * @return string
     */
    public function generateQrCode(User $user): string
    {
        $qrCodeUrl = (new Google2FA())->getQRCodeUrl(
            Craft::$app->getSystemName(),
            $user->email,
            $this->_secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($qrCodeUrl);
    }

    /**
     * Verify provided OTP (code)
     *
     * @param User $user
     * @param string $code
     * @return bool
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     */
    public function verify(User $user, string $code): bool
    {
        $secret = $this->_getStoredSecret($user->id);
        if ($secret === null) {
            return false;
        }

        return (new Google2FA())->verifyKey($secret, $code);
    }

    /**
     * Return user's MFA secret from the database.
     *
     * @param int $userId
     * @return string|null
     */
    private function _getStoredSecret(int $userId): ?string
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
//    private function _storeSecret(int $userId, string $secret): void
//    {
//        $record = AuthenticatorRecord::find()
//            ->where(['userId' => $userId])
//            ->one();
//
//        if (!$record) {
//            $record = new AuthenticatorRecord();
//            $record->userId = $userId;
//        }
//
//        /** @var AuthenticatorRecord $record */
//        $record->mfaSecret = $secret;
//        $record->save();
//    }
}
