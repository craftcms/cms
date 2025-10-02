<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Chippable;
use craft\base\ElementInterface;
use craft\base\Iconic;
use craft\base\UtilityInterface;
use craft\elements\db\NestedElementQueryInterface;
use craft\enums\CmsEdition;
use craft\enums\LicenseKeyStatus;
use craft\errors\BusyResourceException;
use craft\errors\InvalidPluginException;
use craft\errors\StaleResourceException;
use craft\filters\UtilityAccess;
use craft\helpers\Api;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Component;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Search;
use craft\helpers\Session;
use craft\helpers\Update as UpdateHelper;
use craft\helpers\UrlHelper;
use craft\models\Update;
use craft\models\Updates;
use craft\utilities\Updates as UpdatesUtility;
use craft\web\Controller;
use craft\web\ServiceUnavailableHttpException;
use DateInterval;
use Throwable;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\caching\FileDependency;
use yii\web\BadRequestHttpException;
use yii\web\Cookie;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

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
                'when' => fn() => !Craft::$app->getUser()->checkPermission('performUpdates'),
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

        $response = Craft::createGuzzleClient()->get($url);
        $this->response->setCacheHeaders();
        $this->response->getHeaders()->set('content-type', 'application/javascript');
        return $this->asRaw($response->getBody());
    }

    /**
     * Returns the HTML for a control panel icon.
     *
     * @return Response
     * @since 5.7.0
     */
    public function actionIconSvg(): Response
    {
        $this->requireCpRequest();
        $this->requireAcceptsJson();

        return $this->asJson([
            'iconSvg' => Cp::iconSvg($this->request->getRequiredParam('icon')),
        ]);
    }

    /**
     * Returns the latest Craftnet API headers.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @since 3.3.16
     */
    public function actionApiHeaders(): Response
    {
        $this->requireCpRequest();
        return $this->asJson(Api::headers());
    }

    /**
     * Processes an API response’s headers.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @since 3.3.16
     */
    public function actionProcessApiResponseHeaders(): Response
    {
        $this->requireCpRequest();
        $headers = $this->request->getRequiredBodyParam('headers');
        Api::processResponseHeaders($headers);

        // return the updated headers
        return $this->asJson(Api::headers());
    }

    /**
     * Returns update info.
     *
     * @return Response
     * @throws BadRequestHttpException if the request doesn't accept a JSON response
     * @throws ForbiddenHttpException if the user doesn't have permission to perform updates or use the Updates utility
     */
    public function actionCheckForUpdates(): Response
    {
        $this->requireAcceptsJson();

        $updatesService = Craft::$app->getUpdates();

        if ($this->request->getParam('onlyIfCached') && !$updatesService->getIsUpdateInfoCached()) {
            return $this->asJson(['cached' => false]);
        }

        $forceRefresh = (bool)$this->request->getParam('forceRefresh');
        $includeDetails = (bool)$this->request->getParam('includeDetails');

        $updates = $updatesService->getUpdates($forceRefresh);
        return $this->_updatesResponse($updates, $includeDetails);
    }

    /**
     * Caches new update info and then returns it.
     *
     * @return Response
     * @throws ForbiddenHttpException
     * @since 3.3.16
     */
    public function actionCacheUpdates(): Response
    {
        $this->requireAcceptsJson();

        $updateData = $this->request->getBodyParam('updates');
        $updatesService = Craft::$app->getUpdates();
        $updates = $updatesService->cacheUpdates($updateData);
        $includeDetails = (bool)$this->request->getParam('includeDetails');
        return $this->_updatesResponse($updates, $includeDetails);
    }

    /**
     * Returns updates info as JSON
     *
     * @param Updates $updates The updates model
     * @param bool $includeDetails Whether to include update details
     * @return Response
     */
    private function _updatesResponse(Updates $updates, bool $includeDetails): Response
    {
        $allowUpdates = (
            Craft::$app->getConfig()->getGeneral()->allowUpdates &&
            Craft::$app->getConfig()->getGeneral()->allowAdminChanges &&
            Craft::$app->getUser()->checkPermission('performUpdates')
        );

        $res = [
            'total' => $updates->getTotal(),
            'critical' => $updates->getHasCritical(),
            'allowUpdates' => $allowUpdates,
        ];

        if ($includeDetails) {
            $res['updates'] = [
                'cms' => $this->_transformUpdate($allowUpdates, $updates->cms, 'craft', 'Craft CMS'),
                'plugins' => [],
            ];

            $pluginsService = Craft::$app->getPlugins();
            foreach ($updates->plugins as $pluginHandle => $pluginUpdate) {
                try {
                    $pluginInfo = $pluginsService->getPluginInfo($pluginHandle);
                } catch (InvalidPluginException) {
                    continue;
                }
                $res['updates']['plugins'][] = $this->_transformUpdate($allowUpdates, $pluginUpdate, $pluginHandle, $pluginInfo['name']);
            }
        }

        return $this->asJson($res);
    }

    /**
     * Creates a DB backup (if configured to do so), runs any pending Craft,
     * plugin, & content migrations, and syncs `project.yaml` changes in one go.
     *
     * This action can be used as a post-deploy webhook with site deployment
     * services (like [DeployBot](https://deploybot.com/) or [DeployPlace](https://deployplace.com/)) to minimize site
     * downtime after a deployment.
     *
     * @param bool $applyProjectConfigChanges
     * @return Response
     * @throws ServerErrorHttpException
     */
    public function actionMigrate(bool $applyProjectConfigChanges = false): Response
    {
        $this->requirePostRequest();

        $updatesService = Craft::$app->getUpdates();
        $db = Craft::$app->getDb();

        // Get the handles in need of an update
        $handles = $updatesService->getPendingMigrationHandles(true);
        $runMigrations = !empty($handles);

        $projectConfigService = Craft::$app->getProjectConfig();
        if ($applyProjectConfigChanges) {
            $applyProjectConfigChanges = $projectConfigService->areChangesPending(force: true);
        }

        if (!$runMigrations && !$applyProjectConfigChanges) {
            // That was easy
            return $this->response;
        }

        // Bail if Craft is already in maintenance mode
        if (Craft::$app->getIsInMaintenanceMode()) {
            throw new ServiceUnavailableHttpException('Craft is already being updated.');
        }

        // Enable maintenance mode
        Craft::$app->enableMaintenanceMode();

        // Backup the DB?
        $backup = Craft::$app->getConfig()->getGeneral()->getBackupOnUpdate();
        if ($backup) {
            try {
                $backupPath = $db->backup();
            } catch (Throwable $e) {
                Craft::$app->disableMaintenanceMode();
                throw new ServerErrorHttpException('Error backing up the database.', 0, $e);
            }
        }

        $transaction = $db->beginTransaction();

        try {
            // Run the migrations?
            if ($runMigrations) {
                $updatesService->runMigrations($handles);
            }

            // Sync project.yaml?
            if ($applyProjectConfigChanges) {
                try {
                    $projectConfigService->applyExternalChanges();
                } catch (BusyResourceException|StaleResourceException $e) {
                    Craft::$app->getErrorHandler()->logException($e);
                    Craft::warning("Couldn’t apply project config YAML changes: {$e->getMessage()}", __METHOD__);
                }
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            // MySQL may have implicitly committed the transaction
            $restored = $db->getIsPgsql();

            // Do we have a backup?
            if (!$restored && !empty($backupPath)) {
                // Attempt a restore
                try {
                    $db->restore($backupPath);
                    $restored = true;
                } catch (Throwable $restoreException) {
                    // Just log it
                    Craft::$app->getErrorHandler()->logException($restoreException);
                }
            }

            $error = 'An error occurred running new migrations.';
            if ($restored) {
                $error .= ' The database has been restored to its previous state.';
            } elseif (isset($restoreException)) {
                $error .= ' The database could not be restored due to a separate error: ' . $restoreException->getMessage();
            } else {
                $error .= ' The database has not been restored.';
            }

            Craft::$app->disableMaintenanceMode();
            throw new ServerErrorHttpException($error, 0, $e);
        }

        Craft::$app->disableMaintenanceMode();
        return $this->response;
    }

    /**
     * Returns the badge count for the Utilities nav item.
     *
     * @return Response
     */
    public function actionGetUtilitiesBadgeCount(): Response
    {
        $this->requireAcceptsJson();

        $badgeCount = 0;
        $utilities = Craft::$app->getUtilities()->getAuthorizedUtilityTypes();

        foreach ($utilities as $class) {
            /** @var UtilityInterface $class */
            $badgeCount += $class::badgeCount();
        }

        return $this->asJson([
            'badgeCount' => $badgeCount,
        ]);
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
        $user = static::currentUser();

        $currentTime = DateTimeHelper::currentUTCDateTime();
        $tomorrow = $currentTime->add(new DateInterval('P1D'));

        Craft::$app->getUsers()->shunMessageForUser($user->id, $message, $tomorrow);
        return $this->asSuccess();
    }

    /**
     * Displays a licensing issues takeover page.
     *
     * @param array $issues
     * @param string $hash
     * @return Response
     * @internal
     */
    public function actionLicensingIssues(array $issues, string $hash): Response
    {
        $this->requireCpRequest();

        $consoleUrl = rtrim(Craft::$app->getPluginStore()->craftIdEndpoint, '/');
        $cartUrl = UrlHelper::urlWithParams("$consoleUrl/cart/new", [
            'items' => array_map(fn($issue) => $issue[2], $issues),
        ]);

        $cookie = $this->request->getCookies()->get(App::licenseShunCookieName());
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
        $cookieName = App::licenseShunCookieName();
        $oldCookie = $this->request->getCookies()->get($cookieName);
        $data = $oldCookie ? Json::decode($oldCookie->value) : [];

        $newCookie = new Cookie(Craft::cookieConfig([
            'name' => $cookieName,
            'value' => Json::encode([
                'hash' => $this->request->getRequiredBodyParam('hash'),
                'timestamp' => DateTimeHelper::toIso8601(DateTimeHelper::now()),
                'count' => ($data['count'] ?? 0) + 1,
            ]),
            'expire' => DateTimeHelper::now()->modify('+1 year')->getTimestamp(),
        ], $this->request));

        $this->response->getCookies()->add($newCookie);
        return $this->asSuccess();
    }

    /**
     * Tries a Craft edition on for size.
     *
     * @return Response
     * @throws BadRequestHttpException if Craft isn’t allowed to test edition upgrades
     */
    public function actionTryEdition(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $edition = $this->request->getRequiredBodyParam('edition');
        $licensedEdition = Craft::$app->getLicensedEdition() ?? CmsEdition::Solo;

        try {
            $edition = CmsEdition::fromHandle($edition);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), previous: $e);
        }

        // If this is actually an upgrade, make sure that they are allowed to test edition upgrades
        if ($edition->value > $licensedEdition->value && !Craft::$app->getCanTestEditions()) {
            throw new BadRequestHttpException('Craft is not permitted to test edition upgrades from this server');
        }

        if (!Craft::$app->setEdition($edition)) {
            return $this->asFailure();
        }

        return $this->asSuccess();
    }

    /**
     * Switches Craft to the edition it's licensed for.
     *
     * @return Response
     */
    public function actionSwitchToLicensedEdition(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        if (Craft::$app->getHasWrongEdition()) {
            $licensedEdition = Craft::$app->getLicensedEdition();
            $success = Craft::$app->setEdition($licensedEdition);
        } else {
            // Just fake it
            $success = true;
        }

        return $success ? $this->asSuccess() : $this->asFailure();
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
        ArrayHelper::multisort($result, 'name');
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
        $pluginsService = Craft::$app->getPlugins();
        $pluginsService->setPluginLicenseKey($handle, $newKey ?: null);

        // Return the new plugin license info
        return $this->asJson(1);
    }

    /**
     * Transforms an update for inclusion in [[actionCheckForUpdates()]] response JSON.
     *
     * Also sets an `allowed` key on the given update's releases, based on the `allowUpdates` config setting.
     *
     * @param bool $allowUpdates Whether updates are allowed
     * @param Update $update The update model
     * @param string $handle The handle of whatever this update is for
     * @param string $name The name of whatever this update is for
     * @return array
     */
    private function _transformUpdate(bool $allowUpdates, Update $update, string $handle, string $name): array
    {
        $arr = $update->toArray();
        $arr['handle'] = $handle;
        $arr['name'] = $name;
        $arr['latestVersion'] = $update->getLatest()->version ?? null;

        // Make sure that the platform & composer.json PHP version are compatible
        $phpConstraintError = null;
        if (
            $update->phpConstraint &&
            !UpdateHelper::checkPhpConstraint($update->phpConstraint, $phpConstraintError, true)
        ) {
            $arr['status'] = 'phpIssue';
            $arr['statusText'] = $phpConstraintError;
            $arr['ctaUrl'] = false;
        } elseif ($update->status === Update::STATUS_EXPIRED) {
            $arr['statusText'] = Craft::t('app', '<strong>Your license has expired!</strong> Renew your {name} license for another year of amazing updates.', [
                'name' => $name,
            ]);
            $arr['ctaText'] = Craft::t('app', 'Renew for {price}', [
                'price' => Craft::$app->getFormatter()->asCurrency($update->renewalPrice, $update->renewalCurrency),
            ]);
            $arr['ctaUrl'] = UrlHelper::url($update->renewalUrl);

            if ($allowUpdates && Craft::$app->getCanTestEditions()) {
                $arr['altCtaText'] = Craft::t('app', 'Update anyway');
            }
        } else {
            if ($update->abandoned) {
                $arr['statusText'] = Html::tag('strong', Craft::t('app', 'This plugin is no longer maintained.'));
                if ($update->replacementName) {
                    if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
                        $replacementUrl = UrlHelper::url("plugin-store/$update->replacementHandle");
                    } else {
                        $replacementUrl = $update->replacementUrl;
                    }
                    $arr['statusText'] .= ' ' .
                        Craft::t('app', 'The developer recommends using <a href="{url}">{name}</a> instead.', [
                            'url' => $replacementUrl,
                            'name' => $update->replacementName,
                        ]);
                }
            } elseif ($update->status === Update::STATUS_BREAKPOINT) {
                $arr['statusText'] = Craft::t('app', '<strong>You’ve reached a breakpoint!</strong> More updates will become available after you install {update}.', [
                    'update' => $name . ' ' . ($update->getLatest()->version ?? ''),
                ]);
            }

            if ($allowUpdates) {
                $arr['ctaText'] = Craft::t('app', 'Update');
            }
        }

        return $arr;
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
            $licenseInfo = Craft::$app->getApi()->getLicenseInfo(['plugins']);
            $pluginLicenses = $licenseInfo['pluginLicenses'] ?? [];
        }

        $pluginsService = Craft::$app->getPlugins();
        $allPluginInfo = $pluginsService->getAllPluginInfo();

        // Update our records & use all licensed plugins as a starting point
        if (!empty($pluginLicenses)) {
            $defaultIconUrl = Craft::$app->getAssetManager()->getPublishedUrl('@appicons/default-plugin.svg', true);
            $formatter = Craft::$app->getFormatter();
            foreach ($pluginLicenses as $pluginLicenseInfo) {
                if (isset($pluginLicenseInfo['plugin'])) {
                    $pluginInfo = $pluginLicenseInfo['plugin'];
                    $handle = $pluginInfo['handle'];

                    // The same plugin could be associated with this Craft license more than once,
                    // so make sure this is the same license they've entered a license key for, if there is one
                    if (
                        !isset($allPluginInfo[$handle]) ||
                        !$allPluginInfo[$handle]['licenseKey'] ||
                        $pluginsService->normalizePluginLicenseKey(App::parseEnv($allPluginInfo[$handle]['licenseKey'])) === $pluginLicenseInfo['key']
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
                            $result[$handle]['renewalText'] = Craft::t('app', 'Renew for {price}', [
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
                'licenseKey' => $pluginsService->normalizePluginLicenseKey(App::parseEnv($pluginInfo['licenseKey'])),
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
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $imagePath = Craft::getAlias($generalConfig->brokenImagePath);
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

            if (!$id || (!is_numeric($id) && !(is_array($id) && ArrayHelper::isNumeric($id)))) {
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

            // See if there are any provisional drafts we should swap these out with
            ElementHelper::swapInProvisionalDrafts($elements);

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

    /**
     * Returns icon picker options.
     *
     * @return Response
     * @since 5.0.0
     */
    public function actionIconPickerOptions(): Response
    {
        $this->requireCpRequest();
        $this->requireAcceptsJson();

        $search = $this->request->getRequiredBodyParam('search');
        $freeOnly = (bool)($this->request->getBodyParam('freeOnly') ?? false);
        $noSearch = $search === '';

        if ($noSearch) {
            $cache = Craft::$app->getCache();
            $cacheKey = sprintf('icon-picker-options-list-html%s', $freeOnly ? ':free' : '');
            $listHtml = $cache->get($cacheKey);
            if ($listHtml !== false) {
                return $this->asJson([
                    'listHtml' => $listHtml,
                ]);
            }
            $searchTerms = null;
        } else {
            $searchTerms = explode(' ', Search::normalizeKeywords($search));
        }

        $indexPath = '@app/icons/index.php';
        $icons = require Craft::getAlias($indexPath);
        $output = [];
        $scores = [];

        foreach ($icons as $name => $icon) {
            if ($freeOnly && $icon['pro']) {
                continue;
            }

            if ($searchTerms) {
                $score = $this->matchTerms($searchTerms, $icon['name']) * 5 + $this->matchTerms($searchTerms, $icon['terms']);
                if ($score === 0) {
                    continue;
                }
                $scores[] = $score;
            }

            $file = Craft::getAlias("@appicons/$name.svg");
            $output[] = Html::beginTag('li') .
                Html::button(file_get_contents($file), [
                    'class' => 'icon-picker--icon',
                    'title' => $name,
                    'aria' => [
                        'label' => $name,
                    ],
                ]) .
                Html::endTag('li');
        }

        if ($searchTerms) {
            array_multisort($scores, SORT_DESC, $output);
        }

        $listHtml = implode('', $output);

        if ($noSearch) {
            /** @phpstan-ignore-next-line */
            $cache->set($cacheKey, $listHtml, dependency: new FileDependency([
                'fileName' => $indexPath,
            ]));
        }

        return $this->asJson([
            'listHtml' => $listHtml,
        ]);
    }

    private function matchTerms(array $searchTerms, string $indexTerms): int
    {
        $score = 0;

        foreach ($searchTerms as $searchTerm) {
            // extra points for whole word matches
            if (str_contains($indexTerms, " $searchTerm ")) {
                $score += 10;
            } elseif (str_contains($indexTerms, " $searchTerm")) {
                $score += 1;
            } else {
                return 0;
            }
        }

        return $score;
    }
}
