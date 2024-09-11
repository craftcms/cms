<?php

namespace craft\filters;

use Craft;
use craft\elements\User;
use yii\filters\auth\HttpBasicAuth;
use yii\web\IdentityInterface;

class HttpBasicAuthLogin extends HttpBasicAuth
{
    use SiteFilterTrait;

    /**
     * @inheritDoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config + [
            'auth' => [$this, 'auth'],
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
