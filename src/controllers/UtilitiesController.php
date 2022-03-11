<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\UtilityInterface;
use craft\db\Table;
use craft\elements\Asset;
use craft\errors\MigrationException;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Queue;
use craft\helpers\Session;
use craft\queue\jobs\FindAndReplace;
use craft\utilities\ClearCaches;
use craft\utilities\Updates;
use craft\web\assets\utilities\UtilitiesAsset;
use craft\web\Controller;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\caching\TagDependency;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class UtilitiesController extends Controller
{
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
            'toolbarHtml' => $class::toolbarHtml(),
            'footerHtml' => $class::footerHtml(),
            'utilities' => $this->_utilityInfo(),
        ]);
    }

    /**
     * View stack trace for a deprecator log entry.
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn't have access to the Deprecation Warnings utility
     */
    public function actionGetDeprecationErrorTracesModal(): Response
    {
        $this->requirePermission('utility:deprecation-errors');
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $logId = Craft::$app->request->getRequiredParam('logId');
        $html = $this->getView()->renderTemplate('_components/utilities/DeprecationErrors/traces_modal', [
            'log' => Craft::$app->deprecator->getLogById($logId),
        ]);

        return $this->asJson([
            'html' => $html,
        ]);
    }

    /**
     * Deletes all deprecation errors.
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn't have access to the Deprecation Warnings utility
     */
    public function actionDeleteAllDeprecationErrors(): Response
    {
        $this->requirePermission('utility:deprecation-errors');
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        Craft::$app->deprecator->deleteAllLogs();

        return $this->asJson([
            'success' => true,
        ]);
    }

    /**
     * Deletes a deprecation error.
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn't have access to the Deprecation Warnings utility
     */
    public function actionDeleteDeprecationError(): Response
    {
        $this->requirePermission('utility:deprecation-errors');
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $logId = $this->request->getRequiredBodyParam('logId');
        Craft::$app->deprecator->deleteLogById($logId);

        return $this->asJson([
            'success' => true,
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

        $params = $this->request->getRequiredBodyParam('params');

        // Initial request
        $assetIndexerService = Craft::$app->getAssetIndexer();

        if (!empty($params['start'])) {
            $sessionId = $assetIndexerService->getIndexingSessionId();

            $response = [
                'volumes' => [],
                'sessionId' => $sessionId,
            ];

            // Selection of volumes or all volumes?
            if (is_array($params['volumes'])) {
                $volumeIds = $params['volumes'];
            } else {
                $volumeIds = Craft::$app->getVolumes()->getViewableVolumeIds();
            }

            $missingFolders = [];
            $skippedFiles = [];

            foreach ($volumeIds as $volumeId) {
                // Get the indexing list
                $indexList = $assetIndexerService->prepareIndexList($sessionId, $volumeId);

                if (!empty($indexList['error'])) {
                    return $this->asJson($indexList);
                }

                if (isset($indexList['missingFolders'])) {
                    $missingFolders += $indexList['missingFolders'];
                }

                if (isset($indexList['skippedFiles'])) {
                    $skippedFiles = $indexList['skippedFiles'];
                }

                $response['volumes'][] = [
                    'volumeId' => $volumeId,
                    'total' => $indexList['total'],
                ];
            }

            Session::set('assetsVolumesBeingIndexed', $volumeIds);
            Session::set('assetsMissingFolders', $missingFolders);
            Session::set('assetsSkippedFiles', $skippedFiles);

            return $this->asJson([
                'indexingData' => $response,
            ]);
        }

        if (!empty($params['process'])) {
            // Index the file
            $assetIndexerService->processIndexForVolume($params['sessionId'], $params['volumeId'], $params['cacheImages']);

            return $this->asJson([
                'success' => true,
            ]);
        }

        if (!empty($params['overview'])) {
            $missingFiles = $assetIndexerService->getMissingFiles($params['sessionId']);
            $missingFolders = Session::get('assetsMissingFolders') ?? [];
            $skippedFiles = Session::get('assetsSkippedFiles') ?? [];

            if (!empty($missingFiles) || !empty($missingFolders) || !empty($skippedFiles)) {
                return $this->asJson([
                    'confirm' => $this->getView()->renderTemplate('assets/_missing_items', compact('missingFiles', 'missingFolders', 'skippedFiles')),
                    'showDelete' => !empty($missingFiles) || !empty($missingFolders),
                ]);
            }

            $assetIndexerService->deleteStaleIndexingData();
        } elseif (!empty($params['finish'])) {
            if (!empty($params['deleteAsset']) && is_array($params['deleteAsset'])) {
                Db::delete(Table::ASSETTRANSFORMINDEX, [
                    'assetId' => $params['deleteAsset'],
                ]);

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
        }

        return $this->asJson([
            'finished' => 1,
        ]);
    }

    /**
     * Performs a Clear Caches action
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn't have access to the Clear Caches utility
     * @throws BadRequestHttpException
     */
    public function actionClearCachesPerformAction(): Response
    {
        $this->requirePermission('utility:clear-caches');

        $caches = $this->request->getRequiredBodyParam('caches');

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
            } elseif (isset($cacheOption['params'])) {
                call_user_func_array($action, $cacheOption['params']);
            } else {
                $action();
            }
        }

        return $this->asJson([
            'success' => true,
        ]);
    }

    /**
     * Performs an Invalidate Data Caches action.
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn't have access to the Clear Caches utility
     * @throws BadRequestHttpException
     * @since 3.5.0
     */
    public function actionInvalidateTags(): Response
    {
        $this->requirePermission('utility:clear-caches');

        $tags = $this->request->getRequiredBodyParam('tags');
        $cache = Craft::$app->getCache();

        foreach ($tags as $tag) {
            TagDependency::invalidate($cache, $tag);
        }

        return $this->asJson([
            'success' => true,
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

        try {
            $backupPath = Craft::$app->getDb()->backup();
        } catch (\Throwable $e) {
            throw new Exception('Could not create backup: ' . $e->getMessage());
        }

        if (!is_file($backupPath)) {
            throw new Exception("Could not create backup: the backup file doesn't exist.");
        }

        // Zip it up and delete the SQL file
        $zipPath = FileHelper::zip($backupPath);
        unlink($backupPath);

        if (!$this->request->getBodyParam('downloadBackup')) {
            return $this->asJson(['success' => true]);
        }

        return $this->response->sendFile($zipPath, null, [
            'mimeType' => 'application/zip',
        ]);
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

        $params = $this->request->getRequiredBodyParam('params');

        if (!empty($params['find']) && !empty($params['replace'])) {
            Queue::push(new FindAndReplace([
                'find' => $params['find'],
                'replace' => $params['replace'],
            ]));
        }

        return $this->asJson([
            'success' => true,
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
            $this->setSuccessFlash(Craft::t('app', 'Applied new migrations successfully.'));
        } catch (MigrationException $e) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t apply new migrations.'));
        }

        return $this->redirect('utilities/migrations');
    }

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
            'label' => $class::displayName(),
        ]);
    }
}
