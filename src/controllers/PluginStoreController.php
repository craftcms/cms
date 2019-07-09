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
use GuzzleHttp\Exception\RequestException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * The PluginStoreController class is a controller that handles various actions related to the Plugin Store.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */

class PluginStoreController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All plugin store actions require an admin
        $this->requireAdmin();

        parent::init();
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

        $view = $this->getView();
        $view->registerJsFile('https://js.stripe.com/v2/');
        $view->registerJsFile('https://js.stripe.com/v3/');
        $view->registerJs('window.craftApiEndpoint = "' . Craft::$app->getPluginStore()->craftApiEndpoint . '";', View::POS_BEGIN);
        $view->registerJs('window.pluginStoreAppBaseUrl = "' . $pluginStoreAppBaseUrl . '";', View::POS_BEGIN);
        $view->registerJs('window.cmsInfo = ' . Json::encode($cmsInfo) . ';', View::POS_BEGIN);
        $view->registerJs('window.allowUpdates = ' . Json::encode($allowUpdates) . ';', View::POS_BEGIN);
        $view->registerJs('window.cmsLicenseKey = ' . Json::encode(App::licenseKey()) . ';', View::POS_BEGIN);

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
            $redirect = Craft::$app->getRequest()->getPathInfo();
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
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Disconnected from id.craftcms.com.'));
        } catch (\Exception $e) {
            Craft::error('Couldn’t revoke token: ' . $e->getMessage());
            Craft::$app->getSession()->setError(Craft::t('app', 'Disconnected from id.craftcms.com with errors, check the logs.'));
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
            'error' => Craft::$app->getRequest()->getParam('error'),
            'message' => Craft::$app->getRequest()->getParam('message')
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
        return $this->renderTemplate('plugin-store/_special/oauth/modal-callback', [
            'craftIdAccount' => Craft::$app->getPluginStore()->getCraftIdAccount()
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
            $token_type = Craft::$app->getRequest()->getParam('token_type');
            $access_token = Craft::$app->getRequest()->getParam('access_token');
            $expires_in = Craft::$app->getRequest()->getParam('expires_in');

            $token = [
                'access_token' => $access_token,
                'token_type' => $token_type,
                'expires_in' => $expires_in,
            ];

            Craft::$app->getPluginStore()->saveToken($token);

            Craft::$app->getSession()->setNotice(Craft::t('app', 'Connected to craftcms.com.'));

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
     * @throws \yii\base\InvalidConfigException
     */
    public function actionCraftData(): Response
    {
        $this->requireAcceptsJson();

        $data = [];

        // Current user
        $data['currentUser'] = Craft::$app->getUser()->getIdentity();

        // Craft ID account
        $data['craftId'] = Craft::$app->getPluginStore()->getCraftIdAccount();

        // Countries
        $api = Craft::$app->getApi();
        $data['countries'] = $api->getCountries();

        // Craft editions
        $data['editions'] = [];
        foreach ($api->getCmsEditions() as $editionInfo) {
            $data['editions'][$editionInfo['handle']] = [
                'name' => $editionInfo['name'],
                'handle' => $editionInfo['handle'],
                'price' => $editionInfo['price'],
                'renewalPrice' => $editionInfo['renewalPrice'],
            ];
        }

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
     * Returns the Plugin Store’s data.
     *
     * @return Response
     */
    public function actionPluginStoreData()
    {
        $pluginStoreData = Craft::$app->getApi()->getPluginStoreData();

        return $this->asJson($pluginStoreData);
    }

    /**
     * Returns plugin details.
     *
     * @return Response
     */
    public function actionPluginDetails()
    {
        $pluginId = Craft::$app->getRequest()->getParam('pluginId');
        $pluginDetails = Craft::$app->getApi()->getPluginDetails($pluginId);

        return $this->asJson($pluginDetails);
    }

    /**
     * Returns plugin changelog.
     *
     * @return Response
     */
    public function actionPluginChangelog()
    {
        $pluginId = Craft::$app->getRequest()->getParam('pluginId');
        $pluginChangelog = Craft::$app->getApi()->getPluginChangelog($pluginId);

        return $this->asJson($pluginChangelog);
    }

    /**
     * Returns developer details.
     *
     * @return Response
     */
    public function actionDeveloper()
    {
        $developerId = Craft::$app->getRequest()->getParam('developerId');
        $developer = Craft::$app->getApi()->getDeveloper($developerId);

        return $this->asJson($developer);
    }

    /**
     * Checkout.
     *
     * @return Response
     */
    public function actionCheckout()
    {
        $payload = Json::decode(Craft::$app->getRequest()->getRawBody(), true);

        $orderNumber = (isset($payload['orderNumber']) ? $payload['orderNumber'] : null);
        $token = (isset($payload['token']) ? $payload['token'] : null);
        $expectedPrice = (isset($payload['expectedPrice']) ? $payload['expectedPrice'] : null);
        $makePrimary = (isset($payload['makePrimary']) ? $payload['makePrimary'] : false);

        $data = [
            'orderNumber' => $orderNumber,
            'token' => $token,
            'expectedPrice' => $expectedPrice,
            'makePrimary' => $makePrimary,
        ];

        $response = Craft::$app->getApi()->checkout($data);

        return $this->asJson($response);
    }

    /**
     * Create a cart.
     *
     * @return Response
     */
    public function actionCreateCart()
    {
        $data = Json::decode(Craft::$app->getRequest()->getRawBody(), true);
        $response = Craft::$app->getApi()->createCart($data);

        return $this->asJson($response);
    }


    /**
     * Get a cart.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionGetCart()
    {
        $orderNumber = Craft::$app->getRequest()->getRequiredParam('orderNumber');

        try {
            $data = Craft::$app->getApi()->getCart($orderNumber);
            return $this->asJson($data);
        } catch (RequestException $e) {
            $data = Json::decode($e->getResponse()->getBody()->getContents());
            $errorMsg = $e->getMessage();
            if (isset($data['message'])) {
                $errorMsg = $data['message'];
            }

            return $this->asErrorJson($errorMsg);
        }
    }

    /**
     * Update a cart.
     *
     * @return Response
     */
    public function actionUpdateCart()
    {
        $cartData = Json::decode(Craft::$app->getRequest()->getRawBody(), true);

        $orderNumber = $cartData['orderNumber'];
        unset($cartData['orderNumber']);

        try {
            $data = Craft::$app->getApi()->updateCart($orderNumber, $cartData);
        } catch (RequestException $e) {
            $data = Json::decode($e->getResponse()->getBody()->getContents());
        }

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
        $payload = Json::decode(Craft::$app->getRequest()->getRawBody(), true);
        $pluginLicenseKeys = (isset($payload['pluginLicenseKeys']) ? $payload['pluginLicenseKeys'] : []);
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        foreach ($pluginLicenseKeys as $pluginLicenseKey) {
            if (isset($plugins[$pluginLicenseKey['handle']])) {
                Craft::$app->getPlugins()->setPluginLicenseKey($pluginLicenseKey['handle'], $pluginLicenseKey['key']);
            }
        }

        return $this->asJson(['success' => true]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the Plugin Store’s Vue App Base URL for Vue Router.
     *
     * @return string
     */
    private function _getVueAppBaseUrl(): string
    {
        return UrlHelper::rootRelativeUrl(UrlHelper::url('plugin-store'));
    }
}
