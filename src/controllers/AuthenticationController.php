<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\web\Controller;
use yii\web\Response;

class AuthenticationController extends Controller
{
    protected array|bool|int $allowAnonymous = ['authenticate', 'callback'];

    public function beforeAction($action): bool
    {
        if ($action->id === 'callback') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionAuthenticate(string $authenticatorHandle = null): ?Response
    {
        if (!$authenticatorHandle) {
            // TODO: when not set use the default?
            $authenticatorHandle = 'loginForm';
        }

        $authenticator = Craft::$app->getAuthentication()->getAuthenticatorByHandle($authenticatorHandle);
        return $authenticator->handleAuthenticationRequest();
    }
}