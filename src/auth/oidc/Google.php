<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\oidc;

use Craft;
use craft\elements\User;
use craft\errors\AuthFailedException;
use craft\helpers\UrlHelper;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google as GoogleProvider;
use League\OAuth2\Client\Provider\GoogleUser;
use yii\base\Exception;

class Google extends AbstractOpenIdConnectProvider
{
    const SITE_LOGIN_TEMPLATE = 'auth/oidc/google/site-login';
    const CP_LOGIN_TEMPLATE = 'auth/oidc/google/cp-login';

    /**
     * @var string If set, this will be sent to google as the "access_type" parameter.
     * @link https://developers.google.com/identity/protocols/OpenIDConnect#authenticationuriparameters
     */
    public $accessType;

    /**
     * @var string If set, this will be sent to google as the "hd" parameter.
     * @link https://developers.google.com/identity/protocols/OpenIDConnect#authenticationuriparameters
     */
    public $hostedDomain;

    /**
     * @var string If set, this will be sent to google as the "prompt" parameter.
     * @link https://developers.google.com/identity/protocols/OpenIDConnect#authenticationuriparameters
     */
    public $prompt;

    /**
     * @var array List of scopes that will be used for authentication.
     * @link https://developers.google.com/identity/protocols/googlescopes
     */
    public $scopes = [];

    /**
     * @var GoogleProvider|null
     */
    private ?GoogleProvider $_provider = null;

    /**
     * @return GoogleProvider
     */
    private function provider(): GoogleProvider
    {
        if (!$this->_provider) {
            $this->_provider = new GoogleProvider([
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret,
                'accessType' => $this->accessType,
                'hostedDomain' => $this->hostedDomain,
                'prompt' => $this->prompt,
                'scopes' => $this->scopes,
                'redirectUri' => UrlHelper::actionUrl('auth/response', ['provider' => $this->handle], null, false)
            ]);
        }

        return $this->_provider;
    }

    /**
     * @inheritDoc
     */
    public function handleRequest(bool $isLogin): bool
    {
        Craft::$app->getResponse()->redirect(
            $this->provider()->getAuthorizationUrl()
        );

        return true;
    }

    /**
     * @inheritDoc
     */
    public function handleResponse(bool $isLogin): bool
    {
        return $isLogin ? $this->handleLoginRequest() : $this->handleSessionRequest();
    }

    /**
     * @return bool
     * @throws AuthFailedException
     * @throws Exception
     * @throws \yii\web\BadRequestHttpException
     */
    private function handleLoginRequest(): bool
    {
        try {
            return $this->loginUser(
                $this->getUser(
                    $this->getAuthorizedUser()
                )
            );
        } catch (IdentityProviderException $exception) {
            throw new AuthFailedException($this, message: "Failed to retrieve Google Identity.", previous: $exception);
        }
    }

    /**
     * @return bool
     * @throws IdentityProviderException
     * @throws \yii\web\BadRequestHttpException
     */
    private function handleSessionRequest(): bool
    {
        try {
            $this->getAuthorizedUser();

            return true;
        } catch (IdentityProviderException $exception) {
            throw new AuthFailedException($this, message: "Failed to retrieve Google Identity.", previous: $exception);
        }
    }

    /**
     * @return GoogleUser
     * @throws IdentityProviderException
     * @throws \yii\web\BadRequestHttpException
     */
    private function getAuthorizedUser(): GoogleUser
    {
        // Try to get an access token (using the authorization code grant)
        $token = $this->provider()->getAccessToken('authorization_code', [
            'code' => Craft::$app->getRequest()->getRequiredQueryParam('code')
        ]);

        // We got an access token, let's now get the owner details
        return $this->provider()->getResourceOwner($token);
    }

    protected function findUser(GoogleUser $providerUser): ?User
    {
        return Craft::$app->getUsers()->getUserByUsernameOrEmail(
            $providerUser->getEmail()
        );
    }

    protected function getUser(GoogleUser $providerUser): User
    {
        $providerUser = Craft::$app->getUsers()->getUserByUsernameOrEmail(
            $providerUser->getEmail()
        );

        if (!empty($providerUser)) {
            return $providerUser;
        }

        throw new Exception('Todo - Create new user');
        // Create new user
    }

    /**
     * @inheritdoc
     */
    public function siteLoginHtml(): string
    {
        return $this->renderLoginHtml(self::SITE_LOGIN_TEMPLATE);
    }

    /**
     * @inheritdoc
     */
    public function cpLoginHtml(): string
    {
        return $this->renderLoginHtml(self::CP_LOGIN_TEMPLATE);
    }
}
