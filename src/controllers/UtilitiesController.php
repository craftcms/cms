<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Craft;
use craft\web\Controller;
use craft\helpers\App;
use GuzzleHttp\Client;
use Imagine\Gd\Imagine;
use craft\events\RegisterCacheOptionsEvent;
use yii\base\Event;
use craft\helpers\FileHelper;
use yii\base\ErrorException;
use yii\base\Exception;
use ZipArchive;
use craft\tasks\FindAndReplace as FindAndReplaceTask;
use yii\web\NotFoundHttpException;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\db\Query;

class UtilitiesController extends Controller
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterCacheOptionsEvent The event that is triggered when registering cache options.
     */
    const EVENT_REGISTER_CACHE_OPTIONS = 'registerCacheOptions';

    // Public Methods
    // =========================================================================

    /**
     * System Report
     *
     * @return null
     */
    public function actionSystemReport()
    {
        return $this->renderTemplate('utilities/system-report', array(
            'craftVersion' => Craft::$app->version,
            'craftEdition' => 'Craft '.App::editionName(Craft::$app->getEdition()),
            'packages' => [
                'Yii' => \Yii::getVersion(),
                'Twig' => \Twig_Environment::VERSION,
                'Guzzle' => Client::VERSION,
                'Imagine' => Imagine::VERSION,
            ],
            'plugins' => $this->_getPlugins(),
            'requirements' => $this->_getRequirementResults(),
        ));
    }

    /**
     * PHP info
     *
     * @return null
     */
    public function actionPhpInfo()
    {
        return $this->renderTemplate('utilities/php-info', [
            'phpInfo' => $this->_getPhpInfo(),
        ]);
    }

    /**
     * Deprecation Errors
     *
     * @return null
     */
    public function actionDeprecationErrors()
    {
        Craft::$app->getView()->registerCssResource('css/deprecator.css');
        Craft::$app->getView()->registerJsResource('js/deprecator.js');

        return $this->renderTemplate('utilities/deprecation-errors/index', [
            'logs' => Craft::$app->deprecator->getLogs(),
        ]);
    }

    /**
     * View stack trace for a deprecator log entry.
     *
     * @return null
     */
    public function actionGetDeprecationErrorTracesModal()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $logId = Craft::$app->request->getRequiredParam('logId');
        $log = Craft::$app->deprecator->getLogById($logId);

        $html = $this->renderTemplate('utilities/deprecation-errors/_tracesmodal',
            array('log' => $log)
        , true);

        return $this->asJson([
            'html' => $html
        ]);
    }

    /**
     * Deletes all deprecation errors.
     *
     * @return null
     */
    public function actionDeleteAllDeprecationErrors()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        Craft::$app->deprecator->deleteAllLogs();
        /*Craft::$app->end();*/

        return $this->asJson(['success' => true]);
    }

    /**
     * Deletes a deprecation error.
     *
     * @return null
     */
    public function actionDeleteDeprecationError()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $logId = Craft::$app->getRequest()->getRequiredBodyParam('logId');

        Craft::$app->deprecator->deleteLogById($logId);
        /*Craft::$app->end();*/

        return $this->asJson(['success' => true]);
    }

    public function actionAssetIndex()
    {
        /** @var Volume[] $volumes */
        $volumes = Craft::$app->getVolumes()->getAllVolumes();
        $sourceOptions = [];

        foreach ($volumes as $volume) {
            $sourceOptions[] = [
                'label' => $volume->name,
                'value' => $volume->id
            ];
        }

        $html = Craft::$app->getView()->renderTemplate('_includes/forms/checkboxSelect',
            [
                'name' => 'sources',
                'options' => $sourceOptions
            ]);

        Craft::$app->getView()->registerJsResource('js/AssetIndexUtility.js');

        return $this->renderTemplate('utilities/asset-index', [
            'html' => $html,
        ]);
    }

    public function actionAssetIndexPerformAction()
    {
        $params = Craft::$app->getRequest()->getRequiredBodyParam('params');


        // Initial request
        if (!empty($params['start'])) {
            $batches = [];
            $sessionId = Craft::$app->getAssetIndexer()->getIndexingSessionId();

            // Selection of sources or all sources?
            if (is_array($params['sources'])) {
                $sourceIds = $params['sources'];
            } else {
                $sourceIds = Craft::$app->getVolumes()->getViewableVolumeIds();
            }

            $missingFolders = [];
            $skippedFiles = [];
            $grandTotal = 0;

            foreach ($sourceIds as $sourceId) {
                // Get the indexing list
                $indexList = Craft::$app->getAssetIndexer()->prepareIndexList($sessionId, $sourceId);

                if (!empty($indexList['error'])) {
                    return $this->asJson($indexList);
                }

                if (isset($indexList['missingFolders'])) {
                    $missingFolders += $indexList['missingFolders'];
                }

                if (isset($indexList['skippedFiles'])) {
                    $skippedFiles = $indexList['skippedFiles'];
                }

                $batch = [];

                for ($i = 0; $i < $indexList['total']; $i++) {
                    $batch[] = [
                        'params' => [
                            'sessionId' => $sessionId,
                            'sourceId' => $sourceId,
                            'total' => $indexList['total'],
                            'offset' => $i,
                            'process' => 1
                        ]
                    ];
                }

                $batches[] = $batch;
            }

            $batches[] = [
                [
                    'params' => [
                        'overview' => true,
                        'sessionId' => $sessionId,
                    ]
                ]
            ];

            Craft::$app->getSession()->set('assetsSourcesBeingIndexed', $sourceIds);
            Craft::$app->getSession()->set('assetsMissingFolders', $missingFolders);
            Craft::$app->getSession()->set('assetsSkippedFiles', $skippedFiles);

            return $this->asJson([
                'batches' => $batches,
                'total' => $grandTotal
            ]);

        } else if (!empty($params['process'])) {
            // Index the file
            Craft::$app->getAssetIndexer()->processIndexForVolume($params['sessionId'],
                $params['offset'], $params['sourceId']);

            return $this->asJson([
                'success' => true
            ]);
        } else if (!empty($params['overview'])) {
            $sourceIds = Craft::$app->getSession()->get('assetsSourcesBeingIndexed', []);
            $missingFiles = Craft::$app->getAssetIndexer()->getMissingFiles($sourceIds, $params['sessionId']);
            $missingFolders = Craft::$app->getSession()->get('assetsMissingFolders', []);
            $skippedFiles = Craft::$app->getSession()->get('assetsSkippedFiles', []);

            $responseArray = [];

            if (!empty($missingFiles) || !empty($missingFolders) || !empty($skippedFiles)) {
                $responseArray['confirm'] = Craft::$app->getView()->renderTemplate('assets/_missing_items',
                    [
                        'missingFiles' => $missingFiles,
                        'missingFolders' => $missingFolders,
                        'skippedFiles' => $skippedFiles
                    ]);
                $responseArray['params'] = ['finish' => 1];
            }

            // Clean up stale indexing data (all sessions that have all recordIds set)
            $sessionsInProgress = (new Query())
                ->select(['sessionId'])
                ->from(['{{%assetindexdata}}'])
                ->where(['recordId' => null])
                ->groupBy(['sessionId'])
                ->scalar();

            if (empty($sessionsInProgress)) {
                Craft::$app->getDb()->createCommand()
                    ->delete('{{%assetindexdata}}')
                    ->execute();
            } else {
                Craft::$app->getDb()->createCommand()
                    ->delete(
                        '{{%assetindexdata}}',
                        ['not', ['sessionId' => $sessionsInProgress]])
                    ->execute();
            }

            return $this->asJson([
                'batches' => [
                    [
                        $responseArray
                    ]
                ]
            ]);
        } else if (!empty($params['finish'])) {
            if (!empty($params['deleteAsset']) && is_array($params['deleteAsset'])) {
                Craft::$app->getDb()->createCommand()
                    ->delete('assettransformindex', ['assetId' => $params['deleteAsset']])
                    ->execute();

                /** @var Asset[] $assets */
                $assets = Asset::find()
                    ->status(null)
                    ->enabledForSite(false)
                    ->id($params['deleteAsset'])
                    ->all();

                foreach ($assets as $asset) {
                    $asset->keepFileOnDelete = true;
                    Craft::$app->getElements()->deleteElement($asset);
                }
            }

            if (!empty($params['deleteFolder']) && is_array($params['deleteFolder'])) {
                Craft::$app->getAssets()->deleteFoldersByIds($params['deleteFolder'], false);
            }

            return $this->asJson([
                'finished' => 1
            ]);
        }

        return $this->asJson([]);
    }

    public function actionClearCaches()
    {
        $options = [];

        foreach ($this->_getAllCacheOptions() as $cacheOption)
        {
            $options[] = [
                'label' => $cacheOption['label'],
                'value' => $cacheOption['key']
            ];
        }

        Craft::$app->getView()->registerJsResource('js/ClearCachesUtility.js');

        return $this->renderTemplate('utilities/clear-caches', [
            'options' => $options,
        ]);
    }

    public function actionClearCachesPerformAction()
    {
        $params = Craft::$app->getRequest()->getRequiredBodyParam('params');

        if (!isset($params['caches']))
        {
            return $this->asJson(['success' => true]);
        }

        foreach (self::_getAllCacheOptions() as $cacheOption)
        {
            if (is_array($params['caches']) && !in_array($cacheOption['key'], $params['caches'], true))
            {
                continue;
            }

            $action = $cacheOption['action'];

            if (is_string($action))
            {
                try
                {
                    FileHelper::clearDirectory($action);
                }
                catch (\Exception $e)
                {
                    Craft::warning("Could not clear the directory {$action}: ".$e->getMessage());
                }
            }
            else if (isset($cacheOption['params']))
            {
                call_user_func_array($action, $cacheOption['params']);
            }
            else
            {
                call_user_func($action);
            }
        }

        return $this->asJson(['success' => true]);
    }

    public function actionDbBackup()
    {
        Craft::$app->getView()->registerJsResource('js/DbBackupUtility.js');

        return $this->renderTemplate('utilities/db-backup');
    }

    public function actionDbBackupPerformAction()
    {
        $params = Craft::$app->getRequest()->getRequiredBodyParam('params');

        try {
            $backupPath = Craft::$app->getDb()->backup();
        } catch (\Exception $e) {
            throw new Exception('Could not create backup: '.$e->getMessage());
        }

        if (!is_file($backupPath)) {
            throw new Exception("Could not create backup: the backup file doesn't exist.");
        }

        if (empty($params['downloadBackup'])) {
            return null;
        }

        $zipPath = Craft::$app->getPath()->getTempPath().DIRECTORY_SEPARATOR.pathinfo($backupPath, PATHINFO_FILENAME).'.zip';

        if (is_file($zipPath)) {
            try {
                FileHelper::removeFile($zipPath);
            } catch (ErrorException $e) {
                Craft::warning("Unable to delete the file \"{$zipPath}\": ".$e->getMessage());
            }
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new Exception('Cannot create zip at '.$zipPath);
        }

        $filename = pathinfo($backupPath, PATHINFO_BASENAME);
        $zip->addFile($backupPath, $filename);
        $zip->close();

        return $this->asJson([
            'backupFile' => pathinfo($filename, PATHINFO_FILENAME)
        ]);
    }

    /**
     * Returns a database backup zip file to the browser.
     *
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionDownloadBackupFile()
    {
        $filename = Craft::$app->getRequest()->getRequiredQueryParam('filename');
        $filePath = Craft::$app->getPath()->getTempPath().DIRECTORY_SEPARATOR.$filename.'.zip';

        if (!is_file($filePath))
        {
            throw new NotFoundHttpException(Craft::t('app', 'Invalid backup name: {filename}', [
                'filename' => $filename
            ]));
        }

        return Craft::$app->getResponse()->sendFile($filePath);
    }

    public function actionFindAndReplace()
    {
        Craft::$app->getView()->registerJsResource('js/FindAndReplaceUtility.js');

        return $this->renderTemplate('utilities/find-and-replace');
    }

    public function actionFindAndReplacePerformAction()
    {
        $params = Craft::$app->getRequest()->getRequiredBodyParam('params');

        if (!empty($params['find']) && !empty($params['replace'])) {
            Craft::$app->getTasks()->queueTask([
                'type' => FindAndReplaceTask::class,
                'find' => $params['find'],
                'replace' => $params['replace']
            ]);
        }

        return $this->asJson(['success' => true]);
    }

    public function actionSearchIndex()
    {
        Craft::$app->getView()->registerJsResource('js/SearchIndexUtility.js');

        return $this->renderTemplate('utilities/search-index');
    }

    public function actionSearchIndexPerformAction()
    {
        $params = Craft::$app->getRequest()->getRequiredBodyParam('params');

        if (!empty($params['start']))
        {
            // Truncate the searchindex table
            Craft::$app->getDb()->createCommand()
                ->truncateTable('{{%searchindex}}')
                ->execute();

            // Get all the element IDs ever
            $elements = (new Query())
                ->select(['id', 'type'])
                ->from(['{{%elements}}'])
                ->all();

            $batch = [];

            foreach ($elements as $element)
            {
                $batch[] = ['params' => $element];
            }

            return $this->asJson([
                'batches' => [$batch]
            ]);
        }
        else
        {
            /** @var ElementInterface $class */
            $class = $params['type'];

            if ($class::isLocalized())
            {
                $siteIds = Craft::$app->getSites()->getAllSiteIds();
            }
            else
            {
                $siteIds = [Craft::$app->getSites()->getPrimarySite()->id];
            }

            $query = $class::find()
                ->id($params['id'])
                ->status(null)
                ->enabledForSite(false);

            foreach ($siteIds as $siteId)
            {
                $query->siteId($siteId);
                $element = $query->one();

                if ($element)
                {
                    /** @var Element $element */
                    Craft::$app->getSearch()->indexElementAttributes($element);

                    if ($class::hasContent())
                    {
                        $fieldLayout = $element->getFieldLayout();
                        $keywords = [];

                        foreach ($fieldLayout->getFields() as $field)
                        {
                            /** @var Field $field */
                            // Set the keywords for the content's site
                            $fieldValue = $element->getFieldValue($field->handle);
                            $fieldSearchKeywords = $field->getSearchKeywords($fieldValue, $element);
                            $keywords[$field->id] = $fieldSearchKeywords;
                        }

                        Craft::$app->getSearch()->indexElementFields($element->id, $siteId, $keywords);
                    }
                }
            }
        }

        return $this->asJson(['success' => true]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Parses and returns the PHP info.
     *
     * @return array
     */
    private function _getPhpInfo()
    {
        Craft::$app->getConfig()->maxPowerCaptain();

        ob_start();
        phpinfo(-1);
        $phpInfo = ob_get_clean();

        $phpInfo = preg_replace(
            [
                '#^.*<body>(.*)</body>.*$#ms',
                '#<h2>PHP License</h2>.*$#ms',
                '#<h1>Configuration</h1>#',
                "#\r?\n#",
                '#</(h1|h2|h3|tr)>#',
                '# +<#',
                "#[ \t]+#",
                '#&nbsp;#',
                '#  +#',
                '# class=".*?"#',
                '%&#039;%',
                '#<tr>(?:.*?)"src="(?:.*?)=(.*?)" alt="PHP Logo" /></a><h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
                '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
                '# +#',
                '#<tr>#',
                '#</tr>#'
            ],
            [
                '$1',
                '',
                '',
                '',
                '</$1>'."\n",
                '<',
                ' ',
                ' ',
                ' ',
                '',
                ' ',
                '<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'."\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
                '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
                '<tr><td>Zend Engine</td><td>$2</td></tr>'."\n".'<tr><td>Zend Egg</td><td>$1</td></tr>',
                ' ',
                '%S%',
                '%E%'
            ],
            $phpInfo
        );

        $sections = explode('<h2>', strip_tags($phpInfo, '<h2><th><td>'));
        unset($sections[0]);

        $phpInfo = [];
        foreach ($sections as $section) {
            $heading = substr($section, 0, strpos($section, '</h2>'));

            preg_match_all(
                '#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
                $section,
                $parts,
                PREG_SET_ORDER
            );

            foreach ($parts as $row) {
                if (!isset($row[2])) {
                    continue;
                } else if (!isset($row[3]) || $row[2] == $row[3]) {
                    $value = $row[2];
                } else {
                    $value = array_slice($row, 2);
                }

                $phpInfo[$heading][$row[1]] = $value;
            }
        }

        return $phpInfo;
    }

    /**
     * Returns info about the installed plugins
     *
     * @return array
     */
    private function _getPlugins()
    {
        $plugins = [];

        foreach (Craft::$app->getPlugins()->getAllPlugins() as $plugin) {
            /** @var Plugin $plugin */
            $plugins[] = [
                'name' => $plugin->name,
                'version' => $plugin->version,
                'developer' => $plugin->developer,
                'developerUrl' => $plugin->developerUrl,
            ];
        }

        return $plugins;
    }

    /**
     * Runs the requirements checker and returns its results.
     *
     * @return array
     */
    private function _getRequirementResults()
    {
        require_once Craft::$app->getBasePath().DIRECTORY_SEPARATOR.'requirements'.DIRECTORY_SEPARATOR.'RequirementsChecker.php';

        $reqCheck = new \RequirementsChecker();
        $reqCheck->checkCraft();

        return $reqCheck->getResult()['requirements'];
    }

    private function _getAllCacheOptions()
    {
        $runtimePath = Craft::$app->getPath()->getRuntimePath();

        $options = [
            [
                'key' => 'data',
                'label' => Craft::t('app', 'Data caches'),
                'action' => [Craft::$app->getCache(), 'flush']
            ],
            [
                'key' => 'asset',
                'label' => Craft::t('app', 'Asset caches'),
                'action' => $runtimePath.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'cache'
            ],
            [
                'key' => 'rss',
                'label' => Craft::t('app', 'RSS caches'),
                'action' => $runtimePath.DIRECTORY_SEPARATOR.'cache'
            ],
            [
                'key' => 'compiled-templates',
                'label' => Craft::t('app', 'Compiled templates'),
                'action' => $runtimePath.DIRECTORY_SEPARATOR.'compiled_templates'
            ],
            [
                'key' => 'temp-files',
                'label' => Craft::t('app', 'Temp files'),
                'action' => $runtimePath.DIRECTORY_SEPARATOR.'temp'
            ],
            [
                'key' => 'transform-indexes',
                'label' => Craft::t('app', 'Asset transform index'),
                'action' => function() {
                    Craft::$app->getDb()->createCommand()
                        ->truncateTable('{{%assettransformindex}}')
                        ->execute();
                }
            ],
            [
                'key' => 'asset-indexing-data',
                'label' => Craft::t('app', 'Asset indexing data'),
                'action' => function() {
                    Craft::$app->getDb()->createCommand()
                        ->truncateTable('{{%assetindexdata}}')
                        ->execute();
                }
            ],
            [
                'key' => 'template-caches',
                'label' => Craft::t('app', 'Template caches'),
                'action' => [Craft::$app->getTemplateCaches(), 'deleteAllCaches']
            ],
        ];

        $event = new RegisterCacheOptionsEvent([
            'options' => $options
        ]);

        Event::trigger(self::class, self::EVENT_REGISTER_CACHE_OPTIONS, $event);

        return $options;
    }
}
