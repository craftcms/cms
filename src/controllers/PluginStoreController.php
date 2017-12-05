<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
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
        // All plugin store actions require an admin
        $this->requireAdmin();
    }

    /**
     * Plugin Store index.
     *
     * @return Response
     */
    public function actionIndex()
    {
        $vueRouterBase = '/'.Craft::$app->getConfig()->getGeneral()->cpTrigger.'/plugin-store/';

        Craft::$app->getView()->registerJsFile('https://js.stripe.com/v3/');
        Craft::$app->getView()->registerJs('window.craftApiEndpoint = "'.Craft::$app->getPluginStore()->craftApiEndpoint.'";', View::POS_BEGIN);
        Craft::$app->getView()->registerJs('window.stripeApiKey = "'.Craft::$app->getPluginStore()->stripeApiKey.'";', View::POS_BEGIN);
        Craft::$app->getView()->registerJs('window.enableCraftId = "'.Craft::$app->getPluginStore()->enableCraftId.'";', View::POS_BEGIN);
        Craft::$app->getView()->registerJs('window.vueRouterBase = "'.$vueRouterBase.'";', View::POS_BEGIN);
        Craft::$app->getView()->registerAssetBundle(PluginStoreAsset::class);

        return $this->renderTemplate('plugin-store/_index', [
            'enableCraftId' => Craft::$app->getPluginStore()->enableCraftId,
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
            'oauthEndpointUrl' => Craft::$app->getPluginStore()->craftOauthEndpoint,
            'apiEndpointUrl' => Craft::$app->getPluginStore()->craftApiEndpoint,
            'clientId' => Craft::$app->getPluginStore()->craftIdOauthClientId,
            'redirectUri' => UrlHelper::cpUrl('plugin-store/callback'),
        ]);

        $referrer = Craft::$app->getRequest()->getReferrer();

        if ($redirect) {
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
            $url = Craft::$app->getPluginStore()->craftIdEndpoint.'/oauth/revoke';
            $options = ['query' => ['accessToken' => $token->accessToken]];
            $client->request('GET', $url, $options);
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Disconnected from craftcms.com.'));
        } catch (\Exception $e) {
            Craft::error('Couldn’t revoke token: '.$e->getMessage());
            Craft::$app->getSession()->setError(Craft::t('app', 'Disconnected from craftcms.com with errors, check the logs.'));
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

        $view->registerAssetBundle(PluginStoreOauthAsset::class);

        $referrer = Craft::$app->getSession()->get('pluginStoreConnectReferrer');

        $options = [
            'referrer' => $referrer
        ];

        $this->getView()->registerJs('new Craft.PluginStoreOauthCallback('.Json::encode($options).');');

        return $this->renderTemplate('plugin-store/_special/oauth/callback');
    }

    public function actionModalCallback()
    {
        return $this->renderTemplate('plugin-store/_special/oauth/modal-callback', [
            'craftIdAccount' => Craft::$app->getPluginStore()->getCraftIdAccount()
        ]);
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
            if (!Craft::$app->getRequest()->isSecureConnection) {
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

            Craft::$app->getSession()->setNotice(Craft::t('app', 'Connected to craftcms.com.'));

            return $this->asJson([
                'success' => true,
                'redirect' => UrlHelper::cpUrl('plugin-store/account')
            ]);
        } catch (\Exception $e) {
            return $this->asErrorJson($e->getMessage());
        }
    }

    public function actionCraftData()
    {
        $this->requireAcceptsJson();

        $data = [];


        // Installed plugins

        $allPluginInfo = Craft::$app->getPlugins()->getComposerPluginInfo();
        $installedPlugins = [];

        foreach ($allPluginInfo as $handle => $pluginInfo) {
            $installedPlugins[] = [
                'handle' => $handle,
                'packageName' => $pluginInfo['packageName'],
                'version' => $pluginInfo['version'],
            ];
        }

        $data['installedPlugins'] = $installedPlugins;


        // Craft ID account

        $data['craftId'] = Craft::$app->getPluginStore()->getCraftIdAccount();


        // ET upgrade info

        $etResponse = Craft::$app->getEt()->fetchUpgradeInfo();

        if ($etResponse) {
            $upgradeInfo = $etResponse->data;

            $data['countries'] = $upgradeInfo->countries;
            $data['states'] = $upgradeInfo->states;

            $data['upgradeInfo'] = $upgradeInfo;


            // Editions

            $editions = [];
            $formatter = Craft::$app->getFormatter();

            foreach ($upgradeInfo->editions as $edition => $info) {
                $editions[$edition]['price'] = $info['price'];
                $editions[$edition]['formattedPrice'] = $formatter->asCurrency($info['price'], 'USD', [], [], true);

                if (isset($info['salePrice']) && $info['salePrice'] < $info['price']) {
                    $editions[$edition]['salePrice'] = $info['salePrice'];
                    $editions[$edition]['formattedSalePrice'] = $formatter->asCurrency($info['salePrice'], 'USD', [], [], true);
                } else {
                    $editions[$edition]['salePrice'] = null;
                }
            }

            $canTestEditions = Craft::$app->getCanTestEditions();

            $data['editions'] = $editions;
            $data['licensedEdition'] = $etResponse->licensedEdition;
            $data['canTestEditions'] = $canTestEditions;

            $data['CraftEdition'] = Craft::$app->getEdition();
            $data['CraftPersonal'] = Craft::Personal;
            $data['CraftClient'] = Craft::Client;
            $data['CraftPro'] = Craft::Pro;
        }


        // Craft logo

        $data['craftLogo'] = Craft::$app->getAssetManager()->getPublishedUrl('@app/web/assets/pluginstore/dist/', true, 'images/craft.svg');

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
