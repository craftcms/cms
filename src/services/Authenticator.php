<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Craft;
use craft\elements\User;
use craft\helpers\UrlHelper;
use craft\records\Authenticator as AuthenticatorRecord;
use PragmaRX\Google2FA\Google2FA;
use yii\base\Component;
use yii\base\Exception;

/**
 * Authenticator service.
 * An instance of the Authentiator service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getAuthenticator()|`Craft::$app->authenticator`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class Authenticator extends Component
{
    /**
     * @var string the session variable name used to store the identity of the user we're logging in.
     */
    public string $mfaParam = '__mfa';

    /**
     * @var string|null
     */
    private ?string $_secret = null;

    /**
     * Get 2FA secret for initial setup
     *
     * @param User $user
     * @return bool[]|null[]|string[]
     */
    public function getSecret(User $user): array
    {
        $google2fa = new Google2FA();
        $response = [
            'success' => true,
        ];

        $secret = $this->_getStoredSecret($user->id);

        if (empty($secret)) {
            try {
                $secret = $google2fa->generateSecretKey();
                $this->_storeSecret($user->id, $secret);
            } catch (\Exception $e) {
                // todo: log in a new log file????
                $response['success'] = false;
            }
        }

        $this->_secret = $secret;
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'YourCompany',
            $user->email,
            $this->_secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrCode = $writer->writeString($qrCodeUrl);


        return $response + ['secret' => chunk_split($secret, 4, ' '), 'qrCode' => $qrCode];
    }

    public function verifyCode(User $user, string $code): bool
    {
        $secret = $this->_getStoredSecret($user->id);
        if ($secret === null) {
            return false;
        }

        return (new Google2FA())->verifyKey($secret, $code);
    }

    public function mfaEnabled(User $user): bool
    {
        return $user->requireMfa && $this->_getStoredSecret($user->id) !== null;
    }

    /**
     * Return user's 2fa secret from the database.
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
     * Store obtained 2FA secred in the DB against userId
     *
     * @param int $userId
     * @param string $secret
     * @return void
     */
    private function _storeSecret(int $userId, string $secret): void
    {
        $record = AuthenticatorRecord::find()
            ->where(['userId' => $userId])
            ->one();

        if (!$record) {
            $record = new AuthenticatorRecord();
            $record->userId = $userId;
        }

        /* @phpstan-ignore-next-line */ // TODO: fix this properly
        $record->mfaSecret = $secret;
        $record->save();
    }

    public function storeDataForMfaLogin(User $user, int $duration): void
    {
        Craft::$app->getSession()->set($this->mfaParam, [$user->id, $duration]);
    }

    public function getDataForMfaLogin($forget = false): ?array
    {
        $data = Craft::$app->getSession()->get($this->mfaParam);

        if ($data === null) {
            return null;
        }

        if (is_array($data)) {
            [$userId, $duration] = $data;
            $user = User::findOne(['id' => $userId]);

            if ($user === null) {
                throw new Exception(Craft::t('app', 'Can`t find the user.'));
            }

            return compact('user', 'duration');
        }

        if ($forget) {
            $this->removeDataForMfaLogin();
        }
        return null;
    }

    public function removeDataForMfaLogin()
    {
        Craft::$app->getSession()->remove($this->mfaParam);
    }

    public function getMfaUrl($default = null)
    {
        if ($default !== null) {
            $url = UrlHelper::cpUrl($default);
        } else {
            $url = UrlHelper::cpUrl('mfa');
        }
        //Craft::$app->getConfig()->getGeneral()->mfaUrl
        // Strip out any tags that may have gotten in there by accident
        // i.e. if there was a {siteUrl} tag in the Site URL setting, but no matching environment variable,
        // so they ended up on something like http://example.com/%7BsiteUrl%7D/some/path
        return str_replace(['{', '}'], '', $url);
    }
}
