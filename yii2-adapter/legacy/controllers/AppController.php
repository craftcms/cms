<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Carbon\CarbonInterval;
use Craft;
use craft\base\ElementInterface;
use craft\elements\db\NestedElementQueryInterface;
use craft\filters\UtilityAccess;
use craft\helpers\Component;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Session;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Utility\Utilities\Updates as UpdatesUtility;
use DateInterval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Cookie;
use yii\web\Response;
use function CraftCms\Cms\t;

/**
 * The AppController class is a controller that handles various actions for Craft updates, control panel requests,
 * upgrading Craft editions and license requests.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @internal
 */
class AppController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = [
        'migrate' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'broken-image' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'health-check' => self::ALLOW_ANONYMOUS_LIVE,
        'resource-js' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    ];

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            [
                'class' => UtilityAccess::class,
                'utility' => UpdatesUtility::class,
                'only' => ['check-for-updates', 'cache-updates'],
                'when' => fn() => !Gate::check('performUpdates'),
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if ($action->id === 'migrate') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    /**
     * Returns an empty response.
     *
     * @since 3.5.0
     */
    public function actionHealthCheck(): Response
    {
        // All that matters is the 200 response
        $this->response->format = Response::FORMAT_RAW;
        $this->response->data = '';
        return $this->response;
    }

    /**
     * Loads the given JavaScript resource URL and returns it.
     *
     * @param string $url
     * @return Response
     */
    public function actionResourceJs(string $url): Response
    {
        if (!str_starts_with($url, Craft::$app->getAssetManager()->baseUrl)) {
            throw new BadRequestHttpException("$url does not appear to be a resource URL");
        }

        // Close the PHP session in case this takes a while
        Session::close();

        $response = Http::create()->get($url);
        $this->response->setCacheHeaders();
        $this->response->getHeaders()->set('content-type', 'application/javascript');
        return $this->asRaw($response->getBody());
    }

    /**
     * Returns any alerts that should be displayed in the control panel.
     *
     * @return Response
     */
    public function actionGetCpAlerts(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('accessCp');

        $path = $this->request->getRequiredBodyParam('path');

        return $this->asJson([
            'alerts' => Cp::alerts($path, true),
        ]);
    }

    /**
     * Shuns a control panel alert for 24 hours.
     *
     * @return Response
     */
    public function actionShunCpAlert(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('accessCp');

        $message = $this->request->getRequiredBodyParam('message');

        $currentTime = DateTimeHelper::currentUTCDateTime();
        $tomorrow = $currentTime->add(new DateInterval('P1D'));

        Users::shunMessageForUser(Auth::user()->id, $message, $tomorrow);

        return $this->asSuccess();
    }

    /**
     * Displays a licensing issues takeover page.
     *
     * @param array $issues
     * @param string $hash
     * @return Response
     * @internal
     * @deprecated 6.0.0 {@see \CraftCms\Cms\Http\Middleware\EnforceLicenses}
     */
    public function actionLicensingIssues(array $issues, string $hash): Response
    {
        $this->requireCpRequest();

        $consoleUrl = rtrim(Api::craftIdEndpoint(), '/');
        $cartUrl = UrlHelper::urlWithParams("$consoleUrl/cart/new", [
            'items' => array_map(fn($issue) => $issue[2], $issues),
        ]);

        $cookie = $this->request->getCookies()->get(app(License::class)->shunCookieName());
        $data = $cookie ? Json::decode($cookie->value) : null;
        if (($data['hash'] ?? null) !== $hash) {
            $data = null;
        }

        $duration = match ($data['count'] ?? 0) {
            0 => 21,
            1 => 34,
            2 => 55,
            3 => 89,
            4 => 144,
            5 => 233,
            6 => 377,
            7 => 610,
            8 => 987,
            default => 1597,
        };

        $this->response->setNoCacheHeaders();
        return $this->renderTemplate('_special/licensing-issues.twig', [
            'issues' => $issues,
            'hash' => $hash,
            'cartUrl' => $cartUrl,
            'duration' => $duration,
        ])->setStatusCode(402);
    }

    /**
     * Sets the license shun cookie.
     *
     * @return Response
     * @internal
     */
    public function actionSetLicenseShunCookie(): Response
    {
        $cookieName = app(License::class)->shunCookieName();
        $oldCookie = \Illuminate\Support\Facades\Cookie::get($cookieName);
        $data = $oldCookie ? Json::decode($oldCookie) : [];

        \Illuminate\Support\Facades\Cookie::queue(
            $cookieName,
            Json::encode([
                'hash' => $this->request->getRequiredBodyParam('hash'),
                'timestamp' => DateTimeHelper::toIso8601(DateTimeHelper::now()),
                'count' => ($data['count'] ?? 0) + 1,
            ]),
            CarbonInterval::year()->totalMinutes,
        );

        return $this->asSuccess();
    }


    /**
     * Fetches plugin license statuses.
     *
     * @return Response
     */
    public function actionGetPluginLicenseInfo(): Response
    {
        $this->requireAdmin(false);
        $pluginLicenses = $this->request->getBodyParam('pluginLicenses');
        $result = $this->_pluginLicenseInfo($pluginLicenses);
        $result = Arr::sort($result, 'name');
        return $this->asJson($result);
    }

    /**
     * Updates a plugin’s license key.
     *
     * @return Response
     */
    public function actionUpdatePluginLicense(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $handle = $this->request->getRequiredBodyParam('handle');
        $newKey = $this->request->getRequiredBodyParam('key');

        // Get the current key and set the new one
        $pluginsService = app(Plugins::class);
        $pluginsService->setPluginLicenseKey($handle, $newKey ?: null);

        // Return the new plugin license info
        return $this->asJson(1);
    }

    /**
     * Returns plugin license info.
     *
     * @param array|null $pluginLicenses
     * @return array
     */
    private function _pluginLicenseInfo(?array $pluginLicenses = null): array
    {
        $result = [];

        if ($pluginLicenses === null) {
            // Update our records and get license info from the API
            $licenseInfo = app(Api::class)->getLicenseInfo(['plugins']);
            $pluginLicenses = $licenseInfo['pluginLicenses'] ?? [];
        }

        $pluginsService = app(Plugins::class);
        $allPluginInfo = $pluginsService->getAllPluginInfo();

        // Update our records & use all licensed plugins as a starting point
        if (!empty($pluginLicenses)) {
            $defaultIconUrl = Craft::$app->getAssetManager()->getPublishedUrl('@appicons/default-plugin.svg', true);
            $formatter = I18N::getFormatter();
            foreach ($pluginLicenses as $pluginLicenseInfo) {
                if (isset($pluginLicenseInfo['plugin'])) {
                    $pluginInfo = $pluginLicenseInfo['plugin'];
                    $handle = $pluginInfo['handle'];

                    // The same plugin could be associated with this Craft license more than once,
                    // so make sure this is the same license they've entered a license key for, if there is one
                    if (
                        !isset($allPluginInfo[$handle]) ||
                        !$allPluginInfo[$handle]['licenseKey'] ||
                        $pluginsService->normalizePluginLicenseKey(Env::parse($allPluginInfo[$handle]['licenseKey'])) === $pluginLicenseInfo['key']
                    ) {
                        $result[$handle] = [
                            'edition' => null,
                            'isComposerInstalled' => false,
                            'isInstalled' => false,
                            'isEnabled' => false,
                            'licenseKey' => $pluginLicenseInfo['key'],
                            'licensedEdition' => $pluginLicenseInfo['edition'],
                            'licenseKeyStatus' => LicenseKeyStatus::Valid->value,
                            'licenseIssues' => [],
                            'name' => $pluginInfo['name'],
                            'description' => $pluginInfo['shortDescription'],
                            'iconUrl' => $pluginInfo['icon']['url'] ?? $defaultIconUrl,
                            'documentationUrl' => $pluginInfo['documentationUrl'] ?? null,
                            'packageName' => $pluginInfo['packageName'],
                            'latestVersion' => $pluginInfo['latestVersion'],
                            'expired' => $pluginLicenseInfo['expired'],
                        ];
                        if ($pluginLicenseInfo['expired']) {
                            $result[$handle]['renewalUrl'] = $pluginLicenseInfo['renewalUrl'];
                            $result[$handle]['renewalText'] = t('Renew for {price}', [
                                'price' => $formatter->asCurrency($pluginLicenseInfo['renewalPrice'], $pluginLicenseInfo['renewalCurrency']),
                            ]);
                        }
                    }
                }
            }
        }

        // Override with info for the installed plugins
        foreach ($allPluginInfo as $handle => $pluginInfo) {
            $result[$handle] = array_merge($result[$handle] ?? [], [
                'isComposerInstalled' => true,
                'isInstalled' => $pluginInfo['isInstalled'],
                'isEnabled' => $pluginInfo['isEnabled'],
                'version' => $pluginInfo['version'],
                'hasMultipleEditions' => $pluginInfo['hasMultipleEditions'],
                'edition' => $pluginInfo['edition'],
                'licenseKey' => $pluginsService->normalizePluginLicenseKey(Env::parse($pluginInfo['licenseKey'])),
                'licensedEdition' => $pluginInfo['licensedEdition'],
                'licenseKeyStatus' => $pluginInfo['licenseKeyStatus'],
                'licenseIssues' => $pluginInfo['licenseIssues'],
                'isTrial' => $pluginInfo['isTrial'],
                'upgradeAvailable' => $pluginInfo['upgradeAvailable'],
            ]);
        }

        return $result;
    }

    /**
     * Sends a broken image.
     *
     * @return Response
     * @throws InvalidConfigException
     * @since 3.5.0
     */
    public function actionBrokenImage(): Response
    {
        $generalConfig = Cms::config();
        $imagePath = Aliases::get($generalConfig->brokenImagePath);
        if (!is_file($imagePath)) {
            throw new InvalidConfigException("Invalid broken image path: $generalConfig->brokenImagePath");
        }

        $statusCode = $this->response->getStatusCode();
        return $this->response
            ->sendFile($imagePath, null, ['inline' => true])
            ->setStatusCode($statusCode);
    }

    /**
     * Renders an element for the control panel.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @since 5.0.0
     */
    public function actionRenderElements(): Response
    {
        $this->requireCpRequest();
        $this->requireAcceptsJson();

        $criteria = $this->request->getRequiredBodyParam('elements');

        $elementHtml = [];

        foreach ($criteria as $criterion) {
            /** @var class-string<ElementInterface> $elementType */
            $elementType = $criterion['type'];
            $id = $criterion['id'];
            $fieldId = $criterion['fieldId'] ?? null;
            $ownerId = $criterion['ownerId'] ?? null;
            $siteId = $criterion['siteId'];
            $instances = $criterion['instances'];

            if (!$id || (!is_numeric($id) && !(is_array($id) && Arr::isNumeric($id)))) {
                throw new BadRequestHttpException('Invalid element ID');
            }

            $query = $elementType::find()
                ->id($id)
                ->fixedOrder()
                ->drafts(null)
                ->revisions(null)
                ->siteId($siteId)
                ->status(null);

            if ($query instanceof NestedElementQueryInterface) {
                $query
                    ->fieldId($fieldId)
                    ->ownerId($ownerId);
            }

            $elements = $query->all();

            // See if there are any provisional changes we should show
            ElementHelper::loadProvisionalChanges($elements);

            foreach ($elements as $element) {
                foreach ($instances as $key => $instance) {
                    $id = $element->isProvisionalDraft ? $element->getCanonicalId() : $element->id;
                    /** @var 'chip'|'card' $ui */
                    $ui = $instance['ui'] ?? 'chip';
                    $elementHtml[$id][$key] = match ($ui) {
                        'chip' => Cp::elementChipHtml($element, $instance),
                        'card' => Cp::elementCardHtml($element, $instance),
                    };
                }
            }
        }

        $view = Craft::$app->getView();

        return $this->asJson([
            'elements' => $elementHtml,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }

    /**
     * Renders component chips the control panel.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @since 5.0.0
     */
    public function actionRenderComponents(): Response
    {
        $this->requireCpRequest();
        $this->requireAcceptsJson();

        $components = $this->request->getRequiredBodyParam('components');
        $withMenuItems = (bool)$this->request->getBodyParam('withMenuItems');
        $menuId = $this->request->getBodyParam('menuId');

        $componentHtml = [];
        $menuItemHtml = [];

        foreach ($components as $componentInfo) {
            /** @var class-string<Chippable> $componentType */
            $componentType = $componentInfo['type'];
            $id = $componentInfo['id'];

            if (!$id) {
                throw new BadRequestHttpException('Missing component ID');
            }

            $component = $componentType::get($id);
            if ($component) {
                foreach ($componentInfo['instances'] as $config) {
                    if (!empty($config['overrides'])) {
                        Craft::configure($component, Component::cleanseConfig($config['overrides']));
                    }
                    $componentHtml[$componentType][$id][] = Cp::chipHtml($component, $config);
                }

                if ($withMenuItems) {
                    $menuItemHtml[$componentType][$id] = Cp::menuItem([
                        'label' => $component->getUiLabel(),
                        'icon' => $component instanceof Iconic ? $component->getIcon() : null,
                        'attributes' => [
                            'data' => [
                                'type' => get_class($component),
                                'id' => $component->getId(),
                            ],
                        ],
                    ], $menuId);
                }
            }
        }

        $view = Craft::$app->getView();
        $data = [
            'components' => $componentHtml,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ];

        if ($withMenuItems) {
            $data['menuItems'] = $menuItemHtml;
        }

        return $this->asJson($data);
    }
}
