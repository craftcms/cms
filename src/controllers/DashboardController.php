<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Plugin;
use craft\base\Widget;
use craft\base\WidgetInterface;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\i18n\Locale;
use craft\models\CraftSupport;
use craft\web\assets\dashboard\DashboardAsset;
use craft\web\Controller;
use craft\web\UploadedFile;
use DateTime;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use ZipArchive;

/**
 * The DashboardController class is a controller that handles various dashboard related actions including managing
 * widgets, getting [[\craft\widgets\Feed]] feeds and sending [[\craft\widgets\CraftSupport]] support ticket requests.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DashboardController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Dashboard index.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $dashboardService = Craft::$app->getDashboard();
        $view = $this->getView();

        $namespace = $view->getNamespace();

        // Assemble the list of available widget types
        $widgetTypes = $dashboardService->getAllWidgetTypes();
        $widgetTypeInfo = [];
        $view->setNamespace('__NAMESPACE__');

        foreach ($widgetTypes as $widgetType) {
            /** @var WidgetInterface $widgetType */
            if (!$widgetType::isSelectable()) {
                continue;
            }

            $view->startJsBuffer();
            $widget = $dashboardService->createWidget($widgetType);
            $settingsHtml = $view->namespaceInputs((string)$widget->getSettingsHtml());
            $settingsJs = (string)$view->clearJsBuffer(false);

            $class = get_class($widget);
            $widgetTypeInfo[$class] = [
                'iconSvg' => $this->_getWidgetIconSvg($widget),
                'name' => $widget::displayName(),
                'maxColspan' => $widget::maxColspan(),
                'settingsHtml' => $settingsHtml,
                'settingsJs' => $settingsJs,
                'selectable' => true,
            ];
        }

        // Sort them by name
        ArrayHelper::multisort($widgetTypeInfo, 'name');

        $view->setNamespace($namespace);
        $variables = [];

        // Assemble the list of existing widgets
        $variables['widgets'] = [];
        /** @var Widget[] $widgets */
        $widgets = $dashboardService->getAllWidgets();
        $allWidgetJs = '';

        foreach ($widgets as $widget) {
            $view->startJsBuffer();
            $info = $this->_getWidgetInfo($widget);
            $widgetJs = $view->clearJsBuffer(false);

            if ($info === false) {
                continue;
            }

            // If this widget type didn't come back in our getAllWidgetTypes() call, add it now
            if (!isset($widgetTypeInfo[$info['type']])) {
                $widgetTypeInfo[$info['type']] = [
                    'iconSvg' => $this->_getWidgetIconSvg($widget),
                    'name' => $widget::displayName(),
                    'maxColspan' => $widget::maxColspan(),
                    'selectable' => false,
                ];
            }

            $variables['widgets'][] = $info;

            $allWidgetJs .= 'new Craft.Widget("#widget' . $widget->id . '", ' .
                Json::encode($info['settingsHtml']) . ', ' .
                'function(){' . $info['settingsJs'] . '}' .
                ");\n";

            if (!empty($widgetJs)) {
                // Allow any widget JS to execute *after* we've created the Craft.Widget instance
                $allWidgetJs .= $widgetJs . "\n";
            }
        }

        // Include all the JS and CSS stuff
        $view->registerAssetBundle(DashboardAsset::class);
        $view->registerJs('window.dashboard = new Craft.Dashboard(' . Json::encode($widgetTypeInfo) . ');');
        $view->registerJs($allWidgetJs);

        $variables['widgetTypes'] = $widgetTypeInfo;

        return $this->renderTemplate('dashboard/_index', $variables);
    }

    /**
     * Creates a new widget.
     *
     * @return Response
     */
    public function actionCreateWidget(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $dashboardService = Craft::$app->getDashboard();

        $type = $request->getRequiredBodyParam('type');
        $settingsNamespace = $request->getBodyParam('settingsNamespace');

        if ($settingsNamespace) {
            $settings = $request->getBodyParam($settingsNamespace);
        } else {
            $settings = null;
        }

        $widget = $dashboardService->createWidget([
            'type' => $type,
            'settings' => $settings,
        ]);

        return $this->_saveAndReturnWidget($widget);
    }

    /**
     * Saves a widget’s settings.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionSaveWidgetSettings(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $dashboardService = Craft::$app->getDashboard();
        $widgetId = $request->getRequiredBodyParam('widgetId');

        // Get the existing widget
        /** @var Widget $widget */
        $widget = $dashboardService->getWidgetById($widgetId);

        if (!$widget) {
            throw new BadRequestHttpException();
        }

        // Create a new widget model with the new settings
        $settings = $request->getBodyParam('widget' . $widget->id . '-settings');

        $widget = $dashboardService->createWidget([
            'id' => $widget->id,
            'dateCreated' => $widget->dateCreated,
            'dateUpdated' => $widget->dateUpdated,
            'colspan' => $widget->colspan,
            'type' => get_class($widget),
            'settings' => $settings,
        ]);

        return $this->_saveAndReturnWidget($widget);
    }

    /**
     * Deletes a widget.
     *
     * @return Response
     */
    public function actionDeleteUserWidget(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $widgetId = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('id'));
        Craft::$app->getDashboard()->deleteWidgetById($widgetId);

        return $this->asJson(['success' => true]);
    }

    /**
     * Changes the colspan of a widget.
     *
     * @return Response
     */
    public function actionChangeWidgetColspan(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $widgetId = $request->getRequiredBodyParam('id');
        $colspan = $request->getRequiredBodyParam('colspan');

        Craft::$app->getDashboard()->changeWidgetColspan($widgetId, $colspan);

        return $this->asJson(['success' => true]);
    }

    /**
     * Reorders widgets.
     *
     * @return Response
     */
    public function actionReorderUserWidgets(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $widgetIds = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
        Craft::$app->getDashboard()->reorderWidgets($widgetIds);

        return $this->asJson(['success' => true]);
    }

    /**
     * Returns the items for the Feed widget.
     *
     * @return Response
     */
    public function actionGetFeedItems(): Response
    {
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $formatter = Craft::$app->getFormatter();

        $url = $request->getRequiredParam('url');
        $limit = $request->getParam('limit');

        $items = Craft::$app->getFeeds()->getFeedItems($url, $limit);

        foreach ($items as &$item) {
            if (isset($item['date'])) {
                /** @var DateTime $date */
                $date = $item['date'];
                $item['date'] = $formatter->asTimestamp($date, Locale::LENGTH_SHORT);
            } else {
                unset($item['date']);
            }
        }

        return $this->asJson(['items' => $items]);
    }

    /**
     * Creates a new support ticket for the CraftSupport widget.
     *
     * @return Response
     * @throws ErrorException
     * @throws BadRequestHttpException
     * @throws InvalidArgumentException
     */
    public function actionSendSupportRequest(): Response
    {
        $this->requirePostRequest();

        App::maxPowerCaptain();

        $request = Craft::$app->getRequest();
        $widgetId = $request->getBodyParam('widgetId');
        $namespace = $request->getBodyParam('namespace');
        $namespace = $namespace ? $namespace . '.' : '';

        $getHelpModel = new CraftSupport();
        $getHelpModel->fromEmail = $request->getBodyParam($namespace . 'fromEmail');
        $getHelpModel->message = trim($request->getBodyParam($namespace . 'message'));
        $getHelpModel->attachLogs = (bool)$request->getBodyParam($namespace . 'attachLogs');
        $getHelpModel->attachDbBackup = (bool)$request->getBodyParam($namespace . 'attachDbBackup');
        $getHelpModel->attachTemplates = (bool)$request->getBodyParam($namespace . 'attachTemplates');
        $getHelpModel->attachment = UploadedFile::getInstanceByName($namespace . 'attachAdditionalFile');

        if (!$getHelpModel->validate()) {
            return $this->renderTemplate('_components/widgets/CraftSupport/response', [
                'widgetId' => $widgetId,
                'success' => false,
                'errors' => $getHelpModel->getErrors()
            ]);
        }

        $user = Craft::$app->getUser()->getIdentity();

        // Add some extra info about this install
        $message = $getHelpModel->message . "\n\n" .
            "------------------------------\n\n" .
            'Craft ' . Craft::$app->getEditionName() . ' ' . Craft::$app->getVersion();

        /** @var Plugin[] $plugins */
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        if (!empty($plugins)) {
            $pluginNames = [];

            foreach ($plugins as $plugin) {
                $pluginNames[] = $plugin->name . ' ' . $plugin->getVersion() . ' (' . $plugin->developer . ')';
            }

            $message .= "\nPlugins: " . implode(', ', $pluginNames);
        }

        $message .= "\nDomain: " . Craft::$app->getRequest()->getHostInfo();

        $requestParamDefaults = [
            'sFirstName' => $user->getFriendlyName(),
            'sLastName' => $user->lastName ?: 'Doe',
            'sEmail' => $getHelpModel->fromEmail,
            'tNote' => $message,
        ];

        $requestParams = $requestParamDefaults;

        // Create the SupportAttachment zip
        $zipPath = Craft::$app->getPath()->getTempPath() . '/' . StringHelper::UUID() . '.zip';
        try {
            // Create the zip
            $zip = new ZipArchive();

            if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                throw new Exception('Cannot create zip at ' . $zipPath);
            }

            // License key
            if (($licenseKey = App::licenseKey()) !== null) {
                $zip->addFromString('license.key', $licenseKey);
            }

            // Composer files
            try {
                $composerService = Craft::$app->getComposer();
                $zip->addFile($composerService->getJsonPath(), 'composer.json');
                if (($composerLockPath = $composerService->getLockPath()) !== null) {
                    $zip->addFile($composerLockPath, 'composer.lock');
                }
            } catch (Exception $e) {
                // that's fine
            }

            // Logs
            if ($getHelpModel->attachLogs) {
                $logPath = Craft::$app->getPath()->getLogPath();
                if (is_dir($logPath)) {
                    // Grab it all.
                    try {
                        $logFiles = FileHelper::findFiles($logPath, [
                            'only' => ['*.log'],
                            'except' => ['web-404s.log'],
                            'recursive' => false
                        ]);
                    } catch (ErrorException $e) {
                        Craft::warning("Unable to find log files in \"{$logPath}\": " . $e->getMessage(), __METHOD__);
                        $logFiles = [];
                    }

                    foreach ($logFiles as $logFile) {
                        $zip->addFile($logFile, 'logs/' . pathinfo($logFile, PATHINFO_BASENAME));
                    }
                }
            }

            // DB backups
            if ($getHelpModel->attachDbBackup) {
                // Make a fresh database backup of the current schema/data. We want all data from all tables
                // for debugging.
                try {
                    Craft::$app->getDb()->backup();
                } catch (\Throwable $e) {
                    $noteError = "\n\nError backing up database: " . $e->getMessage();
                    $requestParamDefaults['tNote'] .= $noteError;
                    $requestParams['tNote'] .= $noteError;
                }

                $backupPath = Craft::$app->getPath()->getDbBackupPath();
                if (is_dir($backupPath)) {
                    // Get the SQL files in there
                    $backupFiles = FileHelper::findFiles($backupPath, [
                        'only' => ['*.sql'],
                        'recursive' => false
                    ]);

                    // Get the 3 most recent ones
                    $backupTimes = [];
                    foreach ($backupFiles as $backupFile) {
                        $backupTimes[] = filemtime($backupFile);
                    }
                    array_multisort($backupTimes, SORT_DESC, $backupFiles);
                    array_splice($backupFiles, 3);

                    foreach ($backupFiles as $backupFile) {
                        if (pathinfo($backupFile, PATHINFO_EXTENSION) !== 'sql') {
                            continue;
                        }
                        $zip->addFile($backupFile, 'backups/' . pathinfo($backupFile, PATHINFO_BASENAME));
                    }
                }
            }

            // Templates
            if ($getHelpModel->attachTemplates) {
                $templatesPath = Craft::$app->getPath()->getSiteTemplatesPath();
                if (is_dir($templatesPath)) {
                    $templateFiles = FileHelper::findFiles($templatesPath);
                    foreach ($templateFiles as $templateFile) {
                        // Preserve the directory structure within the templates folder
                        $zip->addFile($templateFile, 'templates' . substr($templateFile, strlen($templatesPath)));
                    }
                }
            }

            // Uploaded attachment
            if ($getHelpModel->attachment) {
                $zip->addFile($getHelpModel->attachment->tempName, $getHelpModel->attachment->name);
            }

            // Close and attach the zip
            $zip->close();
            $requestParams['File1_sFilename'] = 'SupportAttachment-' . FileHelper::sanitizeFilename(Craft::$app->getSites()->getPrimarySite()->name) . '.zip';
            $requestParams['File1_sFileMimeType'] = 'application/zip';
            $requestParams['File1_bFileBody'] = base64_encode(file_get_contents($zipPath));
        } catch (\Throwable $e) {
            Craft::warning('Tried to attach debug logs to a support request and something went horribly wrong: ' . $e->getMessage(), __METHOD__);

            // There was a problem zipping, so reset the params and just send the email without the attachment.
            $requestParams = $requestParamDefaults;
            $requestParams['tNote'] .= "\n\nError attaching zip: " . $e->getMessage();
        }

        $requestParams = array_merge($requestParams, ['method' => 'request.create', 'output' => 'xml']);

        // HelpSpot requires form encoded POST params and Guzzles requires this key to do that.
        $requestParams = [
            'form_params' => $requestParams
        ];

        $guzzleClient = Craft::createGuzzleClient(['timeout' => 120, 'connect_timeout' => 120]);

        try {
            $guzzleClient->post('https://support.pixelandtonic.com/api/index.php', $requestParams);
        } catch (\Throwable $e) {
            return $this->renderTemplate('_components/widgets/CraftSupport/response', [
                'widgetId' => $widgetId,
                'success' => false,
                'errors' => [
                    'Support' => $e->getMessage()
                ]
            ]);
        }

        // Delete the zip file
        if (is_file($zipPath)) {
            FileHelper::unlink($zipPath);
        }

        return $this->renderTemplate('_components/widgets/CraftSupport/response', [
            'widgetId' => $widgetId,
            'success' => true,
            'errors' => []
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the info about a widget required to display its body and settings in the Dashboard.
     *
     * @param WidgetInterface $widget
     * @return array|false
     */
    private function _getWidgetInfo(WidgetInterface $widget)
    {
        /** @var Widget $widget */
        $view = $this->getView();
        $namespace = $view->getNamespace();

        // Get the body HTML
        $widgetBodyHtml = $widget->getBodyHtml();

        if ($widgetBodyHtml === false) {
            return false;
        }

        // Get the settings HTML + JS
        $view->setNamespace('widget' . $widget->id . '-settings');
        $view->startJsBuffer();
        $settingsHtml = $view->namespaceInputs((string)$widget->getSettingsHtml());
        $settingsJs = $view->clearJsBuffer(false);

        // Get the colspan (limited to the widget type's max allowed colspan)
        $colspan = ($widget->colspan ?: 1);

        if (($maxColspan = $widget::maxColspan()) && $colspan > $maxColspan) {
            $colspan = $maxColspan;
        }

        $view->setNamespace($namespace);

        return [
            'id' => $widget->id,
            'type' => get_class($widget),
            'colspan' => $colspan,
            'title' => $widget->getTitle(),
            'name' => $widget->displayName(),
            'bodyHtml' => $widgetBodyHtml,
            'settingsHtml' => $settingsHtml,
            'settingsJs' => (string)$settingsJs,
        ];
    }

    /**
     * Returns a widget type’s SVG icon.
     *
     * @param WidgetInterface $widget
     * @return string
     */
    private function _getWidgetIconSvg(WidgetInterface $widget): string
    {
        $iconPath = $widget::iconPath();

        if ($iconPath === null) {
            return $this->_getDefaultWidgetIconSvg($widget);
        }

        if (!is_file($iconPath)) {
            Craft::warning("Widget icon file doesn't exist: {$iconPath}", __METHOD__);
            return $this->_getDefaultWidgetIconSvg($widget);
        }

        if (!FileHelper::isSvg($iconPath)) {
            Craft::warning("Widget icon file is not an SVG: {$iconPath}", __METHOD__);
            return $this->_getDefaultWidgetIconSvg($widget);
        }

        return file_get_contents($iconPath);
    }

    /**
     * Returns the default icon SVG for a given widget type.
     *
     * @param WidgetInterface $widget
     * @return string
     */
    private function _getDefaultWidgetIconSvg(WidgetInterface $widget): string
    {
        return $this->getView()->renderTemplate('_includes/defaulticon.svg', [
            'label' => $widget::displayName()
        ]);
    }

    /**
     * Attempts to save a widget and responds with JSON.
     *
     * @param WidgetInterface $widget
     * @return Response
     */
    private function _saveAndReturnWidget(WidgetInterface $widget): Response
    {
        /** @var Widget $widget */
        $dashboardService = Craft::$app->getDashboard();

        if ($dashboardService->saveWidget($widget)) {
            $info = $this->_getWidgetInfo($widget);
            $view = $this->getView();

            return $this->asJson([
                'success' => true,
                'info' => $info,
                'headHtml' => $view->getHeadHtml(),
                'footHtml' => $view->getBodyHtml(),
            ]);
        }

        $allErrors = [];

        foreach ($widget->getErrors() as $attribute => $errors) {
            foreach ($errors as $error) {
                $allErrors[] = $error;
            }
        }

        return $this->asJson([
            'errors' => $allErrors
        ]);
    }
}
