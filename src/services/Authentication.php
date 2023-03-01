<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\authentication\BaseAuthenticationType;
use craft\elements\User;
use craft\helpers\UrlHelper;
use yii\base\Component;
use yii\base\Exception;

/**
 * Authentication service.
 * An instance of the Authentiation service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getAuthentication()|`Craft::$app->authentication`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class Authentication extends Component
{
    /**
     * @var string the session variable name used to store the identity of the user we're logging in.
     */
    public string $mfaParam = '__mfa';

    private ?BaseAuthenticationType $_authenticator = null;


//    public function mfaEnabled(User $user): bool
//    {
//        return $user->requireMfa && $this->_getStoredSecret($user->id) !== null;
//    }

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

    public function removeDataForMfaLogin(): void
    {
        Craft::$app->getSession()->remove($this->mfaParam);
    }

    public function getMfaUrl($default = null): string
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

    public function getFormHtml(User $user): string
    {
        $this->_authenticator = $user->getDefaultMfaMethod();

        return $this->_authenticator->getFormHtml($user);
    }

    public function verify(User $user, string $verificationCode): bool
    {
        if ($this->_authenticator === null) {
            $this->_authenticator = $user->getDefaultMfaMethod();
        }

        return $this->_authenticator->verify($user, $verificationCode);
    }
}
