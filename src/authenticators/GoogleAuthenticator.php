<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\authenticators;

use Craft;
use craft\base\authenticators\AuthenticationResult;
use craft\base\authenticators\BaseAuthenticator;
use craft\helpers\UrlHelper;
use League\OAuth2\Client\Provider\Google;
use yii\web\Response;

class GoogleAuthenticator extends BaseAuthenticator
{
    public ?string $label = 'Login with Google';

    public ?string $handle = 'google';

    public ?string $clientId = '';

    public ?string $clientSecret = '';

    private Google|null $_provider = null;

    public function getProvider()
    {
        if (!$this->_provider) {
            $this->_provider = new Google([
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret,
                'redirectUri' => $this->getRedirectUri(),
            ]);
        }

        return $this->_provider;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(): AuthenticationResult
    {
        // TODO: Implement authenticate() method.
    }

    public function getRedirectUri()
    {
        return UrlHelper::cpUrl('login/' . $this->handle);
    }

    public function handleAuthenticationRequest(): ?Response
    {
        $code = Craft::$app->getRequest()->getQueryParam('code');

        // If there's no code, go get one
        if (!$code) {
            $authUrl = $this->getProvider()->getAuthorizationUrl();
            // TODO: Set the state to our session
            // $_SESSION['oauth2state'] = $this->getProvider()->getState();
            return Craft::$app->getResponse()->redirect($authUrl);
        }

        $token = $this->getProvider()->getAccessToken('authorization_code', [
            'code' => $code
        ]);

        $googleUser = $this->getProvider()->getResourceOwner($token);

        $craftUser = Craft::$app->getUsers()->getUserByUsernameOrEmail($googleUser->getEmail());
        if (!$craftUser) {
            // Create the user
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();

        // TODO: should this be the expiration of the token?
        $duration = $generalConfig->userSessionDuration;

        $userSession = Craft::$app->getUser();
        $userSession->login($craftUser, $duration);

        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('dashboard'));
    }
}