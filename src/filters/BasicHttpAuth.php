<?php

namespace craft\filters;

use Craft;
use craft\helpers\App;
use yii\base\ActionFilter;
use yii\base\InvalidConfigException;
use yii\web\UnauthorizedHttpException;

class BasicHttpAuth extends ActionFilter
{
    public ?string $username = null;
    public ?string $password = null;
    public string $realm = 'Craft CMS';

    public function __construct($config = [])
    {
        parent::__construct($config + [
            'username' => App::env('CRAFT_HTTP_BASIC_AUTH_USERNAME'),
            'password' => App::env('CRAFT_HTTP_BASIC_AUTH_PASSWORD'),
        ]);
    }

    /**
     * @throws UnauthorizedHttpException
     */
    public function beforeAction($action): bool
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser) {
            return true;
        }

        if (!$this->username || !$this->password) {
            throw new InvalidConfigException('Basic authentication is not configured.');
        }

        list($username, $password) = Craft::$app->getRequest()->getAuthCredentials();

        if ($username === $this->username && $password === $this->password) {
            return true;
        }

        Craft::$app->getResponse()->getHeaders()->set('WWW-Authenticate', "Basic realm=\"{$this->realm}\"");
        throw new UnauthorizedHttpException('Your request was made with invalid credentials.');
    }
}
