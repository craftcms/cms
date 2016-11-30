<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Craft;
use craft\base\Plugin;
use craft\base\Widget;
use craft\base\WidgetInterface;
use craft\dates\DateTime;
use craft\helpers\Io;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\i18n\Locale;
use craft\models\CraftSupport;
use craft\web\Controller;
use craft\web\UploadedFile;
use craft\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use ZipArchive;

/**
 * The DashboardController class is a controller that handles various dashboard related actions including managing
 * widgets, getting [[\craft\widgets\Feed]] feeds and sending [[\craft\widgets\CraftSupport]] support ticket requests.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DashboardController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Dashboard index.
     *
     * @return string
     */
    public function actionIndex()
    {
        $dashboardService = Craft::$app->getDashboard();
        $view = Craft::$app->getView();

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
            $settingsHtml = $view->namespaceInputs($widget->getSettingsHtml());
            $settingsJs = $view->clearJsBuffer(false);

            $class = get_class($widget);
            $widgetTypeInfo[$class] = [
                'iconSvg' => $this->_getWidgetIconSvg($widget),
                'name' => $widget::displayName(),
                'maxColspan' => $widget->getMaxColspan(),
                'settingsHtml' => (string)$settingsHtml,
                'settingsJs' => (string)$settingsJs,
                'selectable' => true,
            ];
        }

        $view->setNamespace($namespace);

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
                    'maxColspan' => $widget->getMaxColspan(),
                    'selectable' => false,
                ];
            }

            $variables['widgets'][] = $info;

            $allWidgetJs .= 'new Craft.Widget("#widget'.$widget->id.'", '.
                Json::encode($info['settingsHtml']).', '.
                'function(){'.$info['settingsJs'].'}'.
                ");\n";

            if ($widgetJs) {
                // Allow any widget JS to execute *after* we've created the Craft.Widget instance
                $allWidgetJs .= $widgetJs."\n";
            }
        }

        // Include all the JS and CSS stuff
        $view->registerCssResource('css/dashboard.css');
        $view->registerJsResource('js/Dashboard.js');
        $view->registerJs('window.dashboard = new Craft.Dashboard('.Json::encode($widgetTypeInfo).');');
        $view->registerJs($allWidgetJs);
        $view->registerTranslations('app', [
            '1 column',
            '{num} columns',
            '{type} Settings',
            'Widget saved.',
            'Couldn’t save widget.',
            'You don’t have any widgets yet.',
        ]);

        $variables['widgetTypes'] = $widgetTypeInfo;

        return $this->renderTemplate('dashboard/_index', $variables);
    }

    /**
     * Creates a new widget.
     *
     * @return Response
     */
    public function actionCreateWidget()
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
     *
     * @throws BadRequestHttpException
     */
    public function actionSaveWidgetSettings()
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
        $settings = $request->getBodyParam('widget'.$widget->id.'-settings');

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
    public function actionDeleteUserWidget()
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
    public function actionChangeWidgetColspan()
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
    public function actionReorderUserWidgets()
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
    public function actionGetFeedItems()
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
     * @return string
     */
    public function actionSendSupportRequest()
    {
        $this->requirePostRequest();

        Craft::$app->getConfig()->maxPowerCaptain();

        $request = Craft::$app->getRequest();
        $widgetId = $request->getBodyParam('widgetId');
        $namespace = $request->getBodyParam('namespace');
        $namespace = $namespace ? $namespace.'.' : '';

        $getHelpModel = new CraftSupport();
        $getHelpModel->fromEmail = $request->getBodyParam($namespace.'fromEmail');
        $getHelpModel->message = trim($request->getBodyParam($namespace.'message'));
        $getHelpModel->attachLogs = (bool)$request->getBodyParam($namespace.'attachLogs');
        $getHelpModel->attachDbBackup = (bool)$request->getBodyParam($namespace.'attachDbBackup');
        $getHelpModel->attachTemplates = (bool)$request->getBodyParam($namespace.'attachTemplates');
        $getHelpModel->attachment = UploadedFile::getInstanceByName($namespace.'attachAdditionalFile');

        if (!$getHelpModel->validate()) {
            return $this->renderTemplate('_components/widgets/CraftSupport/response', [
                'widgetId' => $widgetId,
                'success' => false,
                'errors' => $getHelpModel->getErrors()
            ]);
        }

        $user = Craft::$app->getUser()->getIdentity();

        // Add some extra info about this install
        $message = $getHelpModel->message."\n\n".
            "------------------------------\n\n".
            'Craft '.Craft::$app->getEditionName().' '.Craft::$app->version;

        /** @var Plugin[] $plugins */
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        if ($plugins) {
            $pluginNames = [];

            foreach ($plugins as $plugin) {
                $pluginNames[] = $plugin->name.' '.$plugin->version.' ('.$plugin->developer.')';
            }

            $message .= "\nPlugins: ".implode(', ', $pluginNames);
        }

        $message .= "\nDomain: ".Craft::$app->getRequest()->getHostInfo();

        $requestParamDefaults = [
            'sFirstName' => $user->getFriendlyName(),
            'sLastName' => ($user->lastName ? $user->lastName : 'Doe'),
            'sEmail' => $getHelpModel->fromEmail,
            'tNote' => $message,
        ];

        $requestParams = $requestParamDefaults;

        $hsParams = [
            'helpSpotApiURL' => 'https://support.pixelandtonic.com/api/index.php'
        ];

        // Create the SupportAttachment zip
        $zipPath = Craft::$app->getPath()->getTempPath().'/'.StringHelper::UUID().'.zip';
        try {
            // Create the zip
            $zip = new ZipArchive();

            if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                throw new \Exception('Cannot create zip at '.$zipPath);
            }

            // License key
            $licenseKeyPath = Io::fileExists(Craft::$app->getPath()->getLicenseKeyPath());
            if ($licenseKeyPath !== false) {
                $zip->addFile($licenseKeyPath, 'license.key');
            }

            // Logs
            if ($getHelpModel->attachLogs) {
                $logPath = Io::folderExists(Craft::$app->getPath()->getLogPath());
                if ($logPath !== false) {
                    // Grab it all.
                    $logFiles = Io::getFiles($logPath, true);

                    if ($logFiles === false) {
                        Craft::warning('Could not send log files with the Craft support request.');
                    } else {
                        foreach ($logFiles as $logFile) {
                            $zip->addFile($logFile, 'logs/'.Io::getFilename($logFile));
                        }
                    }
                }
            }

            // DB backups
            if ($getHelpModel->attachDbBackup) {
                // Make a fresh database backup of the current schema/data. We want all data from all tables
                // for debugging.
                Craft::$app->getDb()->backup();

                $backupPath = Io::folderExists(Craft::$app->getPath()->getDbBackupPath());
                if ($backupPath !== false) {
                    // todo: would be nice to be able to get the last 3 modified *.sql* files
                    $backupsFiles = Io::getLastModifiedFiles($backupPath, 3);

                    foreach ($backupsFiles as $backupFile) {
                        if (Io::getExtension($backupFile) != 'sql') {
                            continue;
                        }
                        $zip->addFile($backupFile, 'backups/'.Io::getFilename($backupFile));
                    }
                }
            }

            // Templates
            if ($getHelpModel->attachTemplates) {
                $templatesPath = Io::folderExists(Craft::$app->getPath()->getSiteTemplatesPath());
                if ($templatesPath !== false) {
                    $templateFiles = Io::getFolderContents($templatesPath);
                    if ($templateFiles === false) {
                        Craft::warning('Could not send template files with the Craft support request.');
                    } else {
                        foreach ($templateFiles as $templateFile) {
                            // Skip if it's a directory
                            if (!Io::fileExists($templateFile)) {
                                continue;
                            }
                            // Preserve the directory structure within the templates folder
                            $zip->addFile($templateFile, 'templates'.substr($templateFile, strlen($templatesPath)));
                        }
                    }
                }
            }

            // Uploaded attachment
            if ($getHelpModel->attachment) {
                $zip->addFile($getHelpModel->attachment->tempName, $getHelpModel->attachment->name);
            }

            // Close and attach the zip
            $zip->close();
            $requestParams['File1_sFilename'] = 'SupportAttachment-'.Io::cleanFilename(Craft::$app->getSites()->getPrimarySite()->name).'.zip';
            $requestParams['File1_sFileMimeType'] = 'application/zip';
            $requestParams['File1_bFileBody'] = base64_encode(Io::getFileContents($zipPath));

            // Bump the default timeout because of the attachment.
            $hsParams['callTimeout'] = 120;
        } catch (\Exception $e) {
            Craft::warning('Tried to attach debug logs to a support request and something went horribly wrong: '.$e->getMessage(), __METHOD__);

            // There was a problem zipping, so reset the params and just send the email without the attachment.
            $requestParams = $requestParamDefaults;
        }

        $api = new \HelpSpotAPI($hsParams);
        $result = $api->requestCreate($requestParams);

        // Delete the zip file
        if (Io::fileExists($zipPath)) {
            Io::deleteFile($zipPath);
        }

        if (!$result) {
            $errors = array_filter(preg_split("/(\r\n|\n|\r)/", $api->errors));
            return $this->renderTemplate('_components/widgets/CraftSupport/response', [
                'widgetId' => $widgetId,
                'success' => false,
                'errors' => [
                    'Support' => $errors
                ]
            ]);
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
     *
     * @return array|false
     */
    private function _getWidgetInfo(WidgetInterface $widget)
    {
        /** @var Widget $widget */
        $view = Craft::$app->getView();
        $namespace = $view->getNamespace();

        // Get the body HTML
        $widgetBodyHtml = $widget->getBodyHtml();

        if (!$widgetBodyHtml) {
            return false;
        }

        // Get the settings HTML + JS
        $view->setNamespace('widget'.$widget->id.'-settings');
        $view->startJsBuffer();
        $settingsHtml = $view->namespaceInputs($widget->getSettingsHtml());
        $settingsJs = $view->clearJsBuffer(false);

        // Get the colspan (limited to the widget type's max allowed colspan)
        $colspan = ($widget->colspan ?: 1);

        if (($maxColspan = $widget->getMaxColspan()) && $colspan > $maxColspan) {
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
            'settingsHtml' => (string)$settingsHtml,
            'settingsJs' => (string)$settingsJs,
        ];
    }

    /**
     * Returns a widget type’s SVG icon.
     *
     * @param WidgetInterface $widget
     *
     * @return string
     */
    private function _getWidgetIconSvg(WidgetInterface $widget)
    {
        $iconPath = $widget->getIconPath();

        if ($iconPath && Io::fileExists($iconPath) && FileHelper::getMimeType($iconPath) == 'image/svg+xml') {
            return Io::getFileContents($iconPath);
        }

        return Craft::$app->getView()->renderTemplate('_includes/defaulticon.svg', [
            'label' => $widget::displayName()
        ]);
    }

    /**
     * Attempts to save a widget and responds with JSON.
     *
     * @param WidgetInterface $widget
     *
     * @return Response
     */
    private function _saveAndReturnWidget(WidgetInterface $widget)
    {
        /** @var Widget $widget */
        $dashboardService = Craft::$app->getDashboard();

        if ($dashboardService->saveWidget($widget)) {
            $info = $this->_getWidgetInfo($widget);
            $view = Craft::$app->getView();

            return $this->asJson([
                'success' => true,
                'info' => $info,
                'headHtml' => $view->getHeadHtml(),
                'footHtml' => $view->getBodyHtml(),
            ]);
        } else {
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
}
