<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\UtilityInterface;
use craft\db\Query;
use craft\elements\Asset;
use craft\errors\MigrationException;
use craft\helpers\FileHelper;
use craft\helpers\Path;
use craft\queue\jobs\FindAndReplace;
use craft\utilities\ClearCaches;
use craft\utilities\Updates;
use craft\web\assets\utilities\UtilitiesAsset;
use craft\web\Controller;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use ZipArchive;

class UtilitiesController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Index
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn't have access to any utilities
     */
    public function actionIndex(): Response
    {
        $utilities = Craft::$app->getUtilities()->getAuthorizedUtilityTypes();

        if (empty($utilities)) {
            throw new ForbiddenHttpException('User not permitted to view Utilities');
        }

        // Don't go to the Updates utility by default if there are any others
        if (($key = array_search(Updates::class, $utilities, true)) !== false && count($utilities) > 1) {
            array_splice($utilities, $key, 1);
        }

        /** @var string|UtilityInterface $firstUtility */
        $firstUtility = reset($utilities);

        return $this->redirect('utilities/' . $firstUtility::id());
    }

    /**
     * Show a utility page.
     *
     * @param string $id
     * @return Response
     * @throws NotFoundHttpException if $id is invalid
     * @throws ForbiddenHttpException if the user doesn't have access to the requested utility
     * @throws Exception in case of failure
     */
    public function actionShowUtility(string $id): Response
    {
        $utilitiesService = Craft::$app->getUtilities();

        if (($class = $utilitiesService->getUtilityTypeById($id)) === null) {
            throw new NotFoundHttpException('Invalid utility ID: ' . $id);
        }

        /** @var UtilityInterface $class */
        if ($utilitiesService->checkAuthorization($class) === false) {
            throw new ForbiddenHttpException('User not permitted to access the "' . $class::displayName() . '".');
        }

        $this->getView()->registerAssetBundle(UtilitiesAsset::class);

        return $this->renderTemplate('utilities/_index', [
            'id' => $id,
            'displayName' => $class::displayName(),
            'contentHtml' => $class::contentHtml(),
            'utilities' => $this->_utilityInfo(),
        ]);
    }

    /**
     * View stack trace for a deprecator log entry.
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn't have access to the Deprecation Errors utility
     */
    public function actionGetDeprecationErrorTracesModal(): Response
    {
        $this->requirePermission('utility:deprecation-errors');
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $logId = Craft::$app->request->getRequiredParam('logId');
        $html = $this->getView()->renderTemplate('_components/utilities/DeprecationErrors/traces_modal', [
            'log' => Craft::$app->deprecator->getLogById($logId)
        ]);

        return $this->asJson([
            'html' => $html
        ]);
    }

    /**
     * Deletes all deprecation errors.
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn't have access to the Deprecation Errors utility
     */
    public function actionDeleteAllDeprecationErrors(): Response
    {
        $this->requirePermission('utility:deprecation-errors');
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        Craft::$app->deprecator->deleteAllLogs();

        return $this->asJson([
            'success' => true
        ]);
    }

    /**
     * Deletes a deprecation error.
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn't have access to the Deprecation Errors utility
     */
    public function actionDeleteDeprecationError(): Response
    {
        $this->requirePermission('utility:deprecation-errors');
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $logId = Craft::$app->getRequest()->getRequiredBodyParam('logId');
        Craft::$app->deprecator->deleteLogById($logId);

        return $this->asJson([
            'success' => true
        ]);
    }

    /**
     * Performs an Asset Index action
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn't have access to the Asset Indexes utility
     */
    public function actionAssetIndexPerformAction(): Response
    {
        $this->requirePermission('utility:asset-indexes');

        $params = Craft::$app->getRequest()->getRequiredBodyParam('params');

        // Initial request
        if (!empty($params['start'])) {
            $batches = [];
            $sessionId = Craft::$app->getAssetIndexer()->getIndexingSessionId();

            // Selection of volumes or all volumes?
            if (is_array($params['volumes'])) {
                $volumeIds = $params['volumes'];
            } else {
                $volumeIds = Craft::$app->getVolumes()->getViewableVolumeIds();
            }

            $missingFolders = [];
            $skippedFiles = [];
            $grandTotal = 0;

            foreach ($volumeIds as $volumeId) {
                // Get the indexing list
                $indexList = Craft::$app->getAssetIndexer()->prepareIndexList($sessionId, $volumeId);

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
                            'volumeId' => $volumeId,
                            'total' => $indexList['total'],
                            'process' => 1,
                            'cacheImages' => $params['cacheImages']
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

            Craft::$app->getSession()->set('assetsVolumesBeingIndexed', $volumeIds);
            Craft::$app->getSession()->set('assetsMissingFolders', $missingFolders);
            Craft::$app->getSession()->set('assetsSkippedFiles', $skippedFiles);

            return $this->asJson([
                'batches' => $batches,
                'total' => $grandTotal
            ]);
        }

        if (!empty($params['process'])) {
            // Index the file
            Craft::$app->getAssetIndexer()->processIndexForVolume($params['sessionId'], $params['volumeId'], $params['cacheImages']);

            return $this->asJson([
                'success' => true
            ]);
        }

        if (!empty($params['overview'])) {
            $missingFiles = Craft::$app->getAssetIndexer()->getMissingFiles($params['sessionId']);
            $missingFolders = Craft::$app->getSession()->get('assetsMissingFolders', []);
            $skippedFiles = Craft::$app->getSession()->get('assetsSkippedFiles', []);

            $responseArray = [];

            if (!empty($missingFiles) || !empty($missingFolders) || !empty($skippedFiles)) {
                $responseArray['confirm'] = $this->getView()->renderTemplate('assets/_missing_items',
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

            if (!empty($responseArray)) {
                return $this->asJson([
                    'batches' => [
                        [
                            $responseArray
                        ]
                    ]
                ]);
            }
        } else if (!empty($params['finish'])) {
            if (!empty($params['deleteAsset']) && is_array($params['deleteAsset'])) {
                Craft::$app->getDb()->createCommand()
                    ->delete('{{%assettransformindex}}', ['assetId' => $params['deleteAsset']])
                    ->execute();

                /** @var Asset[] $assets */
                $assets = Asset::find()
                    ->anyStatus()
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

    /**
     * Performs a Clear Caches action
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn't have access to the Clear Caches utility
     */
    public function actionClearCachesPerformAction(): Response
    {
        $this->requirePermission('utility:clear-caches');

        $caches = Craft::$app->getRequest()->getRequiredBodyParam('caches');

        if (!isset($caches)) {
            return $this->asJson([
                'success' => true
            ]);
        }

        foreach (ClearCaches::cacheOptions() as $cacheOption) {
            if (is_array($caches) && !in_array($cacheOption['key'], $caches, true)) {
                continue;
            }

            $action = $cacheOption['action'];

            if (is_string($action)) {
                try {
                    FileHelper::clearDirectory($action);
                } catch (InvalidArgumentException $e) {
                    // the directory doesn't exist
                } catch (\Throwable $e) {
                    Craft::warning("Could not clear the directory {$action}: " . $e->getMessage(), __METHOD__);
                }
            } else if (isset($cacheOption['params'])) {
                call_user_func_array($action, $cacheOption['params']);
            } else {
                $action();
            }
        }

        return $this->asJson([
            'success' => true
        ]);
    }

    /**
     * Performs a DB Backup action
     *
     * @return Response|null
     * @throws ForbiddenHttpException if the user doesn't have access to the DB Backup utility
     * @throws Exception if the backup could not be created
     */
    public function actionDbBackupPerformAction()
    {
        $this->requirePermission('utility:db-backup');

        $params = Craft::$app->getRequest()->getRequiredBodyParam('params');

        try {
            $backupPath = Craft::$app->getDb()->backup();
        } catch (\Throwable $e) {
            throw new Exception('Could not create backup: ' . $e->getMessage());
        }

        if (!is_file($backupPath)) {
            throw new Exception("Could not create backup: the backup file doesn't exist.");
        }

        if (empty($params['downloadBackup'])) {
            return $this->asJson(['success' => true]);
        }

        $zipPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . pathinfo($backupPath, PATHINFO_FILENAME) . '.zip';

        if (is_file($zipPath)) {
            try {
                FileHelper::unlink($zipPath);
            } catch (ErrorException $e) {
                Craft::warning("Unable to delete the file \"{$zipPath}\": " . $e->getMessage(), __METHOD__);
            }
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new Exception('Cannot create zip at ' . $zipPath);
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
     * @throws ForbiddenHttpException if the user doesn't have access to the DB Backup utility
     * @throws NotFoundHttpException if the requested backup cannot be found
     */
    public function actionDownloadBackupFile(): Response
    {
        $this->requirePermission('utility:db-backup');

        $filename = Craft::$app->getRequest()->getRequiredQueryParam('filename');
        $filePath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename . '.zip';

        if (!is_file($filePath) || !Path::ensurePathIsContained($filePath)) {
            throw new NotFoundHttpException(Craft::t('app', 'Invalid backup name: {filename}', [
                'filename' => $filename
            ]));
        }

        return Craft::$app->getResponse()->sendFile($filePath);
    }

    /**
     * Performs a Find And Replace action
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn't have access to the Find an Replace utility
     */
    public function actionFindAndReplacePerformAction(): Response
    {
        $this->requirePermission('utility:find-replace');

        $params = Craft::$app->getRequest()->getRequiredBodyParam('params');

        if (!empty($params['find']) && !empty($params['replace'])) {
            Craft::$app->getQueue()->push(new FindAndReplace([
                'find' => $params['find'],
                'replace' => $params['replace'],
            ]));
        }

        return $this->asJson([
            'success' => true
        ]);
    }

    /**
     * Performs a Search Index action
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn't have access to the Search Indexes utility
     */
    public function actionSearchIndexPerformAction(): Response
    {
        $this->requirePermission('utility:search-indexes');

        $params = Craft::$app->getRequest()->getRequiredBodyParam('params');

        if (!empty($params['start'])) {
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

            foreach ($elements as $element) {
                $batch[] = ['params' => $element];
            }

            return $this->asJson([
                'batches' => [$batch]
            ]);
        }

        /** @var ElementInterface $class */
        $class = $params['type'];

        if ($class::isLocalized()) {
            $siteIds = Craft::$app->getSites()->getAllSiteIds();
        } else {
            $siteIds = [Craft::$app->getSites()->getPrimarySite()->id];
        }

        $query = $class::find()
            ->id($params['id'])
            ->anyStatus();

        foreach ($siteIds as $siteId) {
            $query->siteId($siteId);
            $element = $query->one();

            if ($element) {
                /** @var Element $element */
                Craft::$app->getSearch()->indexElementAttributes($element);

                if ($class::hasContent() && ($fieldLayout = $element->getFieldLayout()) !== null) {
                    $keywords = [];

                    foreach ($fieldLayout->getFields() as $field) {
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

        return $this->asJson([
            'success' => true
        ]);
    }

    /**
     * Applies new migrations
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn't have access to the Migrations utility
     */
    public function actionApplyNewMigrations()
    {
        $this->requirePermission('utility:migrations');

        $migrator = Craft::$app->getContentMigrator();

        try {
            $migrator->up();
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Applied new migrations successfully.'));
        } catch (MigrationException $e) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t apply new migrations.'));
        }

        return $this->redirect('utilities/migrations');
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns info about all of the utilities.
     *
     * @return array
     */
    private function _utilityInfo()
    {
        $info = [];

        foreach (Craft::$app->getUtilities()->getAuthorizedUtilityTypes() as $class) {
            $info[] = [
                'id' => $class::id(),
                'iconSvg' => $this->_getUtilityIconSvg($class),
                'displayName' => $class::displayName(),
                'iconPath' => $class::iconPath(),
                'badgeCount' => $class::badgeCount(),
            ];
        }

        return $info;
    }

    /**
     * Returns a utility type’s SVG icon.
     *
     * @param string $class
     * @return string
     */
    private function _getUtilityIconSvg(string $class): string
    {
        /** @var UtilityInterface|string $class */
        $iconPath = $class::iconPath();

        if ($iconPath === null) {
            return $this->_getDefaultUtilityIconSvg($class);
        }

        if (!is_file($iconPath)) {
            Craft::warning("Utility icon file doesn't exist: {$iconPath}", __METHOD__);
            return $this->_getDefaultUtilityIconSvg($class);
        }

        if (!FileHelper::isSvg($iconPath)) {
            Craft::warning("Utility icon file is not an SVG: {$iconPath}", __METHOD__);
            return $this->_getDefaultUtilityIconSvg($class);
        }

        return file_get_contents($iconPath);
    }

    /**
     * Returns the default icon SVG for a given utility type.
     *
     * @param string $class
     * @return string
     */
    private function _getDefaultUtilityIconSvg(string $class): string
    {
        /** @var UtilityInterface $class */
        return $this->getView()->renderTemplate('_includes/defaulticon.svg', [
            'label' => $class::displayName()
        ]);
    }
}
