<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

use Craft;
use craft\elements\User;
use yii\filters\auth\HttpBasicAuth;
use yii\web\IdentityInterface;

/**
 * Filter for adding basic HTTP authentication user credentials to site requests.
 *
 * @see https://www.yiiframework.com/doc/api/2.0/yii-filters-auth-httpbasicauth
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
class BasicHttpAuthLogin extends HttpBasicAuth
{
    use SiteFilterTrait, BasicHttpAuthTrait;

    /**
     * @inheritdoc
     */
    public $realm;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config + [
            'auth' => [$this, 'auth'],
            'realm' => Craft::$app->getSystemName(),
        ]);
    }

    protected function auth($username, $password): ?IdentityInterface
    {
        if (!$username || !$password) {
            return null;
        }

        $user = User::find()->username($username)->one();
        $identity = $user?->findIdentity($user->id);

        if ($identity && Craft::$app->getSecurity()->validatePassword($password, $identity->password)) {
            return $identity;
        }

        return null;
    }
}
