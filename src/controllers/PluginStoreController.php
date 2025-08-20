<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\errors\InvalidLicenseKeyException;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\web\assets\pluginstore\PluginStoreAsset;
use craft\web\Controller;
use craft\web\View;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Support\Json;
use yii\base\Exception;
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
        if (!parent::beforeAction($action)) {
            return false;
        }

        // All plugin store actions require an admin
        $this->requireAdmin(false);

        return true;
    }

    /**
     * Plugin Store index.
     *
     * @return Response
     * @throws Exception
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
                'edition' => Craft::$app->edition->handle(),
            ],
            'cmsLicenseKey' => App::licenseKey(),
            'cmsEditions' => array_map(fn(Edition $edition) => $edition->handle(), Edition::cases()),
            'craftIdAccessToken' => $this->getCraftIdAccessToken(),
            'phpVersion' => App::phpVersion(),
            'composerPhpVersion' => app(Composer::class)->getConfig()['config']['platform']['php'] ?? null,
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
        $data['licensedEdition'] = Craft::$app->getLicensedEdition()?->value;
        $data['canTestEditions'] = Craft::$app->getCanTestEditions();
        $data['CraftEdition'] = Craft::$app->edition->value;
        $data['CraftSolo'] = Edition::Solo->value;
        $data['CraftTeam'] = Edition::Team->value;
        $data['CraftPro'] = Edition::Pro->value;
        $data['CraftEnterprise'] = Edition::Enterprise->value;

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
        $plugins = app(Plugins::class)->getAllPlugins();

        foreach ($pluginLicenseKeys as $pluginLicenseKey) {
            if (isset($plugins[$pluginLicenseKey['handle']])) {
                app(Plugins::class)->setPluginLicenseKey($pluginLicenseKey['handle'], $pluginLicenseKey['key']);
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
