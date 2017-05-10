<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Craft;
use craft\web\assets\pluginstore\PluginStoreAsset;
use craft\web\Controller;
use craft\helpers\UrlHelper;
use craft\helpers\Json;
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

        return $this->renderTemplate('plugin-store/_index', [
            'plugins' => (isset($plugins) ? $plugins : null),
            'error' => (isset($error) ? $error : null)
        ]);
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

            $pingResponse = $client->request('GET', 'ping');
            $ping = json_decode($pingResponse->getBody(), true);

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
            'ping' => (isset($ping) ? $ping : null),
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
    public function actionConnect()
    {
        $provider = new CraftId([
            'clientId'     => '1234567890',
            'redirectUri'  => UrlHelper::cpUrl('plugin-store/callback'),
        ]);

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
            $client->request('GET', 'https://craftcms.dev/oauth/revoke', ['query' => ['accessToken' => $token->accessToken]]);
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

        $this->getView()->registerJs('new Craft.PluginStoreOauthCallback();');

        return $this->renderTemplate('plugin-store/_special/oauth/callback');
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
}