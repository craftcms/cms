<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Craft;
use craft\web\assets\pluginstore\PluginStoreAsset;
use craft\web\assets\pluginstorevue\PluginStoreVueAsset;
use craft\web\Controller;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craftcms\oauth2\client\provider\CraftId;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * The PluginStoreController class is a controller that handles various actions related to the Plugin Store.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
        // All system setting actions require an admin
        $this->requireAdmin();
    }

    /**
     * Plugin Store index.
     *
     * @return Response
     */
    public function actionIndex()
    {
        Craft::$app->getView()->registerAssetBundle(PluginStoreVueAsset::class);
        $openModal = Craft::$app->getSession()->get('pluginStore.openModal');

        if($openModal) {
            Craft::$app->getSession()->remove('pluginStore.openModal');
        }

        return $this->renderTemplate('plugin-store/_index', [
            'openModal' => $openModal,
        ]);
    }

    /**
     * @return Response
     */
    public function actionAllPlugins()
    {
        Craft::$app->getView()->registerAssetBundle(PluginStoreVueAsset::class);

        return $this->renderTemplate('plugin-store/_all-plugins');
    }

    /**
     * @return Response
     */
    public function actionCategory($categoryId)
    {
        Craft::$app->getView()->registerAssetBundle(PluginStoreVueAsset::class);

        return $this->renderTemplate('plugin-store/categories/_category', [
            'categoryId' => $categoryId,
        ]);
    }

    /**
     * @return Response
     */
    public function actionDeveloper($developerId)
    {
        Craft::$app->getView()->registerAssetBundle(PluginStoreVueAsset::class);

        return $this->renderTemplate('plugin-store/developers/_developer', [
            'developerId' => $developerId,
        ]);
    }


    /**
     * @return Response
     */
    public function actionInstall()
    {
        Craft::$app->getView()->registerAssetBundle(PluginStoreVueAsset::class);

        return $this->renderTemplate('plugin-store/_install');
    }



    public function actionPlugin($slug)
    {
        $client = Craft::$app->getPluginStore()->getClient();

        try {

            $pluginJson = $client->request('GET', 'plugins/'.$slug);
            $pluginResponse = json_decode($pluginJson->getBody(), true);

            if(!isset($pluginResponse['error'])) {
                $plugin = $pluginResponse;
            } else {
                $error = $pluginResponse['error'];
            }
        }
        catch(\Exception $e)
        {
            $error = $e->getMessage();
        }

        Craft::$app->getView()->registerAssetBundle(PluginStoreAsset::class);
        // Craft::$app->getView()->registerAssetBundle(PluginStoreAppAsset::class);

        return $this->renderTemplate('plugin-store/_plugin/details', [
            'slug' => $slug,
            'plugin' => (isset($plugin) ? $plugin : null),
            'error' => (isset($error) ? $error : null)
        ]);
    }

    public function actionVuePlugin($slug)
    {
        $client = Craft::$app->getPluginStore()->getClient();

        try {

            $pluginJson = $client->request('GET', 'plugins/'.$slug);
            $pluginResponse = json_decode($pluginJson->getBody(), true);

            if(!isset($pluginResponse['error'])) {
                $plugin = $pluginResponse;
            } else {
                $error = $pluginResponse['error'];
            }
        }
        catch(\Exception $e)
        {
            $error = $e->getMessage();
        }

        Craft::$app->getView()->registerAssetBundle(PluginStoreAsset::class);
        Craft::$app->getView()->registerAssetBundle(PluginStoreVueAsset::class);

        return $this->renderTemplate('plugin-store/vue/_plugin', [
            'slug' => $slug,
            'plugin' => (isset($plugin) ? $plugin : null),
            'error' => (isset($error) ? $error : null)
        ]);
    }

    public function actionPluginLicense($slug)
    {
        $client = Craft::$app->getPluginStore()->getClient();

        try {

            $pluginJson = $client->request('GET', 'plugins/'.$slug);
            $pluginResponse = json_decode($pluginJson->getBody(), true);

            if(!isset($pluginResponse['error'])) {
                $plugin = $pluginResponse;
            } else {
                $error = $pluginResponse['error'];
            }
        }
        catch(\Exception $e)
        {
            $error = $e->getMessage();
        }

        Craft::$app->getView()->registerAssetBundle(PluginStoreAsset::class);
        // Craft::$app->getView()->registerAssetBundle(PluginStoreAppAsset::class);

        return $this->renderTemplate('plugin-store/_plugin/license', [
            'slug' => $slug,
            'plugin' => (isset($plugin) ? $plugin : null),
            'error' => (isset($error) ? $error : null)
        ]);
    }

    public function actionCart()
    {
        $client = Craft::$app->getPluginStore()->getClient();

        try {
            $pluginsJson = $client->request('GET', 'plugins');
            $pluginsResponse = json_decode($pluginsJson->getBody(), true);

            if(!isset($pluginsResponse['error'])) {
                $plugins = $pluginsResponse['data'];
            } else {
                $error = $pluginsResponse['error'];
            }
        }
        catch(\Exception $e)
        {
            $error = $e->getMessage();
        }

        Craft::$app->getView()->registerAssetBundle(PluginStoreAsset::class);

        return $this->renderTemplate('plugin-store/_cart', [
            'plugins' => (isset($plugins) ? $plugins : null),
            'error' => (isset($error) ? $error : null)
        ]);
    }

    public function actionSearch()
    {
        $client = Craft::$app->getPluginStore()->getClient();

        $results = [];

        $q = Craft::$app->getRequest()->getParam('q');

        $searchJsonResponse = $client->request('GET', 'plugins/search', ['query' => ['q' => $q]]);
        $searchResponse = json_decode($searchJsonResponse->getBody(), true);

        if(!isset($searchResponse['error'])) {
            $results = $searchResponse['data'];
        }

        // Send the entry back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'results' => $results
        ]);
    }

    public function actionApiPlugin()
    {
        $slug = Craft::$app->getRequest()->getParam('slug');
        $client = Craft::$app->getPluginStore()->getClient();

        $pluginJson = $client->request('GET', 'plugins/'.$slug);
        $pluginResponse = json_decode($pluginJson->getBody(), true);

        if(isset($pluginResponse['error'])) {
            $error = $pluginResponse['error'];

            return $this->asErrorJson($error);
        }

        $data = $pluginResponse;

        return $this->asJson($data);
    }

    /**
     * Returns the plugins.
     *
     * @return Response
     */
    public function actionApiPlugins()
    {
        $client = Craft::$app->getPluginStore()->getClient();

        $response = $client->request('GET', 'plugins');

        $data = json_decode($response->getBody(), true);

        return $this->asJson($data);
    }

    /**
     * Account
     *
     * @return string
     */
    public function actionAccount()
    {
        $token = Craft::$app->getPluginStore()->getToken();

        if($token && $token->hasExpired()) {
            $token = null;
        }

        try {
            $client = Craft::$app->getPluginStore()->getClient();
            $pluginsResponse = $client->request('GET', 'plugins');
            $plugins = json_decode($pluginsResponse->getBody(), true);

            if($token)
            {
                $accountResponse = $client->request('GET', 'account');
                $account = json_decode($accountResponse->getBody(), true);

                if($account['error']) {
                    $error = $account['error'];
                    $account = null;
                }
            }
        }
        catch(\Exception $e)
        {
            $error = $e->getMessage();
        }

        Craft::$app->getView()->registerAssetBundle(PluginStoreAsset::class);

        return $this->renderTemplate('plugin-store/account/_index', [
            'token' => $token,
            'plugins' => (isset($plugins) ? $plugins : null),
            'account' => (isset($account) ? $account : null),
            'error' => (isset($error) ? $error : null)
        ]);
    }

    /**
     * Connect
     *
     * @return Response
     */
    public function actionConnect($redirect = null)
    {
        $provider = new CraftId([
            'clientId'     => '1234567890',
            'redirectUri'  => UrlHelper::cpUrl('plugin-store/callback'),
        ]);

        $referrer = Craft::$app->getRequest()->getReferrer();

        if($redirect) {
            $referrer = $redirect;
        }

        Craft::$app->getSession()->set('pluginStoreConnectReferrer', $referrer);

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
     * Disconnect
     *
     * @return Response
     */
    public function actionDisconnect()
    {
        $token = Craft::$app->getPluginStore()->getToken();

        // Revoke token
        $client = Craft::createGuzzleClient();

        try {
            $client->request('GET', 'https://craftid.dev/oauth/revoke', ['query' => ['accessToken' => $token->accessToken]]);
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Disconnected from CraftCMS.dev.'));
        } catch(\Exception $e) {
            Craft::error('Couldn’t revoke token.');
            Craft::$app->getSession()->setError(Craft::t('app', 'Disconnected from CraftCMS.dev with errors, check the logs.'));
        }

        Craft::$app->getPluginStore()->deleteToken();

        // Redirect
        return $this->redirectToPostedUrl();
    }

    /**
     * Callback
     *
     * @return Response
     */
    public function actionCallback()
    {
        $view = $this->getView();

        $view->registerAssetBundle(PluginStoreAsset::class);

        $referrer = Craft::$app->getSession()->get('pluginStoreConnectReferrer');

        $options = [
            'referrer' => $referrer
        ];

        $this->getView()->registerJs('new Craft.PluginStoreOauthCallback('.Json::encode($options).');');

        return $this->renderTemplate('plugin-store/_special/oauth/callback');
    }

    public function actionModalCallback()
    {
        Craft::$app->getSession()->set('pluginStore.openModal', true);

        return $this->redirect('plugin-store');
    }

    /**
     * Save token
     *
     * @return Response
     */
    public function actionSaveToken()
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        try {
            if(!Craft::$app->getRequest()->isSecureConnection) {
                throw new BadRequestHttpException('OAuth requires a secure callback URL.');
            }

            $token_type = Craft::$app->getRequest()->getParam('token_type');
            $access_token = Craft::$app->getRequest()->getParam('access_token');
            $expires_in = Craft::$app->getRequest()->getParam('expires_in');

            $token = [
                'access_token' => $access_token,
                'token_type' => $token_type,
                'expires_in' => $expires_in,
            ];

            Craft::$app->getPluginStore()->saveToken($token);

            Craft::$app->getSession()->setNotice(Craft::t('app', 'Connected to CraftCMS.dev.'));

            return $this->asJson([
                'success' => true,
                'redirect' => UrlHelper::cpUrl('plugin-store/account')
            ]);
        } catch(\Exception $e) {
            return $this->asErrorJson($e->getMessage());
        }
    }

    public function actionCraftData()
    {
        $this->requireAcceptsJson();

        $data = Craft::$app->getSession()->get('pluginStore.craftData');

        if(!$data) {
            $data = [
                'installedPlugins' => []
            ];
        }

        $data['craftId'] = Craft::$app->getPluginStore()->getCraftIdAccount();

        $etResponse = Craft::$app->getEt()->fetchUpgradeInfo();

        if($etResponse) {
            $upgradeInfo = $etResponse->data;

            $data['countries'] = $upgradeInfo->countries;
            $data['states'] = $upgradeInfo->states;
        }

        return $this->asJson($data);
    }

    public function actionSaveCraftData()
    {
        $this->requirePostRequest();

        $postData = Craft::$app->getRequest()->getParam('craftData');

        $sessionData = [
            'installedPlugins' => $postData['installedPlugins']
        ];

        Craft::$app->getSession()->set('pluginStore.craftData', $sessionData);

        return $this->asJson([]);
    }

    public function actionClearCraftData()
    {
        Craft::$app->getSession()->remove('pluginStore.craftData');

        return $this->asJson(true);
    }
}