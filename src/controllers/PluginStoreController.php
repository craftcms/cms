<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\assets\pluginstore\PluginStoreAsset;
use craft\web\assets\pluginstoreoauth\PluginStoreOauthAsset;
use craft\web\Controller;
use craft\web\View;
use craftcms\oauth2\client\provider\CraftId;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * The PluginStoreController class is a controller that handles various actions related to the Plugin Store.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @internal
 */
class PluginStoreController extends Controller
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // All plugin store actions require an admin
        $this->requireAdmin();
    }

    /**
     * Plugin Store index.
     *
     * @return Response
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex(): Response
    {
        $pluginStoreAppBaseUrl = $this->_getVueAppBaseUrl();

        $cmsInfo = [
            'version' => Craft::$app->getVersion(),
            'edition' => strtolower(Craft::$app->getEditionName()),
        ];

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $allowUpdates = $generalConfig->allowUpdates && $generalConfig->allowAdminChanges;

        $craftIdAccessToken = $this->getCraftIdAccessToken();

        $view = $this->getView();
        $view->registerJsFile('https://js.stripe.com/v2/');
        $view->registerJs('window.craftApiEndpoint = "' . Craft::$app->getPluginStore()->craftApiEndpoint . '";', View::POS_BEGIN);
        $view->registerJs('window.pluginStoreAppBaseUrl = "' . $pluginStoreAppBaseUrl . '";', View::POS_BEGIN);
        $view->registerJs('window.cmsInfo = ' . Json::encode($cmsInfo) . ';', View::POS_BEGIN);
        $view->registerJs('window.allowUpdates = ' . Json::encode($allowUpdates) . ';', View::POS_BEGIN);
        $view->registerJs('window.cmsLicenseKey = ' . Json::encode(App::licenseKey()) . ';', View::POS_BEGIN);
        $view->registerJs('window.craftIdAccessToken = ' . Json::encode($craftIdAccessToken) . ';', View::POS_BEGIN);

        $view->registerAssetBundle(PluginStoreAsset::class);

        return $this->renderTemplate('plugin-store/_index');
    }

    /**
     * Connect to id.craftcms.com.
     *
     * @param string|null $redirectUrl
     *
     * @return Response
     */
    public function actionConnect(string $redirectUrl = null): Response
    {
        $callbackUrl = UrlHelper::cpUrl('plugin-store/callback');

        $provider = new CraftId([
            'oauthEndpointUrl' => Craft::$app->getPluginStore()->craftOauthEndpoint,
            'apiEndpointUrl' => Craft::$app->getPluginStore()->craftApiEndpoint,
            'clientId' => Craft::$app->getPluginStore()->craftIdOauthClientId,
            'redirectUri' => $callbackUrl,
        ]);

        if (!$redirectUrl) {
            $redirect = $this->request->getPathInfo();
            $redirectUrl = UrlHelper::url($redirect);
        }

        Craft::$app->getSession()->set('pluginStoreConnectRedirectUrl', $redirectUrl);

        $authorizationUrl = $provider->getAuthorizationUrl([
            'scope' => [
                'purchasePlugins',
                'existingPlugins',
                'transferPluginLicense',
                'deassociatePluginLicense',
            ],
            'response_type' => 'token'
        ]);

        return $this->redirect($authorizationUrl);
    }

    /**
     * Disconnect from id.craftcms.com.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionDisconnect(): Response
    {
        $token = Craft::$app->getPluginStore()->getToken();

        // Revoke token
        $client = Craft::createGuzzleClient();

        try {
            $url = Craft::$app->getPluginStore()->craftIdEndpoint . '/oauth/revoke';
            $options = ['query' => ['accessToken' => $token->accessToken]];
            $client->request('GET', $url, $options);
            $this->setSuccessFlash(Craft::t('app', 'Disconnected from id.craftcms.com.'));
        } catch (\Exception $e) {
            Craft::error('Couldn’t revoke token: ' . $e->getMessage());
            $this->setFailFlash(Craft::t('app', 'Disconnected from id.craftcms.com with errors, check the logs.'));
        }

        Craft::$app->getPluginStore()->deleteToken();

        // Redirect
        return $this->redirectToPostedUrl();
    }

    /**
     * OAuth callback.
     *
     * @return Response
     * @throws \yii\base\InvalidConfigException
     */
    public function actionCallback(): Response
    {
        $view = $this->getView();

        $view->registerAssetBundle(PluginStoreOauthAsset::class);

        $redirectUrl = Craft::$app->getSession()->get('pluginStoreConnectRedirectUrl');

        $options = [
            'redirectUrl' => $redirectUrl,
            'error' => $this->request->getParam('error'),
            'message' => $this->request->getParam('message')
        ];

        $this->getView()->registerJs('new Craft.PluginStoreOauthCallback(' . Json::encode($options) . ');');

        return $this->renderTemplate('plugin-store/_special/oauth/callback');
    }

    /**
     * OAuth modal callback.
     *
     * @return Response
     */
    public function actionModalCallback(): Response
    {
        $craftIdAccessToken = $this->getCraftIdAccessToken();

        return $this->renderTemplate('plugin-store/_special/oauth/modal-callback', [
            'craftIdAccessToken' => $craftIdAccessToken
        ]);
    }

    /**
     * Saves a token.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionSaveToken(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        try {
            $token_type = $this->request->getParam('token_type');
            $access_token = $this->request->getParam('access_token');
            $expires_in = $this->request->getParam('expires_in');

            $token = [
                'access_token' => $access_token,
                'token_type' => $token_type,
                'expires_in' => $expires_in,
            ];

            Craft::$app->getPluginStore()->saveToken($token);

            $this->setSuccessFlash(Craft::t('app', 'Connected to craftcms.com.'));

            return $this->asJson([
                'success' => true,
                'redirect' => UrlHelper::cpUrl('plugin-store/account')
            ]);
        } catch (\Exception $e) {
            return $this->asErrorJson($e->getMessage());
        }
    }

    /**
     * Returns Craft data.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionCraftData(): Response
    {
        $this->requireAcceptsJson();

        $data = [];

        // Current user
        $currentUser = Craft::$app->getUser()->getIdentity();
        $data['currentUser'] = $currentUser->getAttributes(['email']);

        // Craft license/edition info
        $data['licensedEdition'] = Craft::$app->getLicensedEdition();
        $data['canTestEditions'] = Craft::$app->getCanTestEditions();
        $data['CraftEdition'] = Craft::$app->getEdition();
        $data['CraftSolo'] = Craft::Solo;
        $data['CraftPro'] = Craft::Pro;

        // Logos
        $data['craftLogo'] = Craft::$app->getAssetManager()->getPublishedUrl('@app/web/assets/pluginstore/dist/', true, 'images/craft.svg');
        $data['poweredByStripe'] = Craft::$app->getAssetManager()->getPublishedUrl('@app/web/assets/pluginstore/dist/', true, 'images/powered_by_stripe.svg');
        $data['defaultPluginSvg'] = Craft::$app->getAssetManager()->getPublishedUrl('@app/web/assets/pluginstore/dist/', true, 'images/default-plugin.svg');

        return $this->asJson($data);
    }

    /**
     * Save plugin license keys.
     *
     * @return Response
     * @throws \craft\errors\InvalidLicenseKeyException
     * @throws \craft\errors\InvalidPluginException
     */
    public function actionSavePluginLicenseKeys()
    {
        $payload = Json::decode($this->request->getRawBody(), true);
        $pluginLicenseKeys = (isset($payload['pluginLicenseKeys']) ? $payload['pluginLicenseKeys'] : []);
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        foreach ($pluginLicenseKeys as $pluginLicenseKey) {
            if (isset($plugins[$pluginLicenseKey['handle']])) {
                Craft::$app->getPlugins()->setPluginLicenseKey($pluginLicenseKey['handle'], $pluginLicenseKey['key']);
            }
        }

        return $this->asJson(['success' => true]);
    }

    /**
     * Returns the Plugin Store’s Vue App Base URL for Vue Router.
     *
     * @return string
     */
    private function _getVueAppBaseUrl(): string
    {
        return UrlHelper::rootRelativeUrl(UrlHelper::url('plugin-store'));
    }

    /**
     * Returns the Craft ID access token.
     *
     * @return string|null
     */
    private function getCraftIdAccessToken()
    {
        $craftIdAccessToken = null;
        $pluginStoreService = Craft::$app->getPluginStore();
        $craftIdToken = $pluginStoreService->getToken();

        if ($craftIdToken && $craftIdToken->accessToken !== null) {
            $craftIdAccessToken = $craftIdToken->accessToken;
        }

        return $craftIdAccessToken;
    }
}
