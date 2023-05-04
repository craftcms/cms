<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\errors\InvalidLicenseKeyException;
use craft\errors\InvalidPluginException;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\assets\pluginstore\PluginStoreAsset;
use craft\web\Controller;
use craft\web\View;
use yii\base\InvalidConfigException;
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
    public function beforeAction($action): bool
    {
        // All plugin store actions require an admin
        $this->requireAdmin(false);

        return parent::beforeAction($action);
    }

    /**
     * Plugin Store index.
     *
     * @return Response
     * @throws \yii\base\Exception
     * @throws InvalidConfigException
     */
    public function actionIndex(): Response
    {
        $view = $this->getView();
        $view->registerJsFile('https://js.stripe.com/v2/');

        $variables = [
            'craftIdEndpoint' => Craft::$app->getPluginStore()->craftIdEndpoint,
            'craftApiEndpoint' => Craft::$app->getPluginStore()->craftApiEndpoint,
            'pluginStoreAppBaseUrl' => $this->_getVueAppBaseUrl(),
            'cmsInfo' => [
                'version' => Craft::$app->getVersion(),
                'edition' => strtolower(Craft::$app->getEditionName()),
            ],
            'cmsLicenseKey' => App::licenseKey(),
            'craftIdAccessToken' => $this->getCraftIdAccessToken(),
            'phpVersion' => App::phpVersion(),
            'composerPhpVersion' => Craft::$app->getComposer()->getConfig()['config']['platform']['php'] ?? null,
        ];

        $view->registerJsWithVars(
            fn($variables) => "Object.assign(window, $variables)",
            [$variables],
            View::POS_BEGIN
        );

        $view->registerAssetBundle(PluginStoreAsset::class);

        return $this->renderTemplate('plugin-store/_index.twig');
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
        $currentUser = static::currentUser();
        $data['currentUser'] = $currentUser->getAttributes(['email']);

        // Craft license/edition info
        $data['licensedEdition'] = Craft::$app->getLicensedEdition();
        $data['canTestEditions'] = Craft::$app->getCanTestEditions();
        $data['CraftEdition'] = Craft::$app->getEdition();
        $data['CraftSolo'] = Craft::Solo;
        $data['CraftPro'] = Craft::Pro;

        // Logos
        $data['craftLogo'] = Craft::$app->getAssetManager()->getPublishedUrl('@app/web/assets/pluginstore/dist/', true, 'images/craft.svg');

        return $this->asJson($data);
    }

    /**
     * Save plugin license keys.
     *
     * @return Response
     * @throws InvalidLicenseKeyException
     * @throws InvalidPluginException
     */
    public function actionSavePluginLicenseKeys(): Response
    {
        $payload = Json::decode($this->request->getRawBody(), true);
        $pluginLicenseKeys = ($payload['pluginLicenseKeys'] ?? []);
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        foreach ($pluginLicenseKeys as $pluginLicenseKey) {
            if (isset($plugins[$pluginLicenseKey['handle']])) {
                Craft::$app->getPlugins()->setPluginLicenseKey($pluginLicenseKey['handle'], $pluginLicenseKey['key']);
            }
        }

        return $this->asSuccess();
    }

    /**
     * Returns the Plugin Store’s Vue App Base URL for Vue Router.
     *
     * @return string
     */
    private function _getVueAppBaseUrl(): string
    {
        $url = UrlHelper::rootRelativeUrl(UrlHelper::url('plugin-store'));
        return UrlHelper::removeParam($url, 'site');
    }

    /**
     * Returns the Craft Console access token.
     *
     * @return string|null
     */
    private function getCraftIdAccessToken(): ?string
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
