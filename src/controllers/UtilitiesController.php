<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\UtilityInterface;
use craft\errors\MigrationException;
use craft\filters\UtilityAccess;
use craft\helpers\Cp;
use craft\helpers\FileHelper;
use craft\helpers\Queue;
use craft\queue\jobs\FindAndReplace;
use craft\utilities\ClearCaches;
use craft\utilities\DbBackup;
use craft\utilities\DeprecationErrors;
use craft\utilities\FindAndReplace as FindAndReplaceUtility;
use craft\utilities\Migrations;
use craft\utilities\Updates;
use craft\utilities\Upgrade;
use craft\web\assets\utilities\UtilitiesAsset;
use craft\web\Controller;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\caching\TagDependency;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class UtilitiesController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            [
                'class' => UtilityAccess::class,
                'utility' => DeprecationErrors::class,
                'only' => ['get-deprecation-error-traces-modal', 'delete-all-deprecation-errors', 'delete-deprecation-error'],
            ],
            [
                'class' => UtilityAccess::class,
                'utility' => ClearCaches::class,
                'only' => ['clear-caches-perform-action', 'invalidate-tags'],
            ],
            [
                'class' => UtilityAccess::class,
                'utility' => DbBackup::class,
                'only' => ['db-backup-perform-action'],
            ],
            [
                'class' => UtilityAccess::class,
                'utility' => FindAndReplaceUtility::class,
                'only' => ['find-and-replace-perform-action'],
            ],
            [
                'class' => UtilityAccess::class,
                'utility' => Migrations::class,
                'only' => ['apply-new-migrations'],
            ],
        ]);
    }

    /**
     * Index
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn’t have access to any utilities
     */
    public function actionIndex(): Response
    {
        $utilities = Craft::$app->getUtilities()->getAuthorizedUtilityTypes();

        if (empty($utilities)) {
            throw new ForbiddenHttpException('User not permitted to view Utilities');
        }

        // Don’t go to the Updates or Upgrade utilities by default if there are any others
        $firstUtility = null;
        foreach ($utilities as $utility) {
            if (!in_array($utility, [Updates::class, Upgrade::class])) {
                $firstUtility = $utility;
                break;
            }
        }

        if (!$firstUtility) {
            $firstUtility = reset($utilities);
        }

        return $this->redirect('utilities/' . $firstUtility::id());
    }

    /**
     * Show a utility page.
     *
     * @param string $id
     * @return Response
     * @throws ForbiddenHttpException if the user doesn’t have access to the requested utility
     * @throws Exception in case of failure
     */
    public function actionShowUtility(string $id): Response
    {
        $utilitiesService = Craft::$app->getUtilities();
        $class = $utilitiesService->getUtilityTypeById($id);

        if ($class === null) {
            return $this->run('index');
        }

        if ($utilitiesService->checkAuthorization($class) === false) {
            throw new ForbiddenHttpException(sprintf('User not permitted to access the “%s” utility.', $class::displayName()));
        }

        $this->getView()->registerAssetBundle(UtilitiesAsset::class);

        return $this->renderTemplate('utilities/_index.twig', [
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
     * @throws ForbiddenHttpException if the user doesn’t have access to the Deprecation Warnings utility
     */
    public function actionGetDeprecationErrorTracesModal(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $logId = Craft::$app->request->getRequiredParam('logId');
        $html = $this->getView()->renderTemplate('_components/utilities/DeprecationErrors/traces_modal.twig', [
            'log' => Craft::$app->deprecator->getLogById($logId),
        ]);

        return $this->asJson([
            'html' => $html,
        ]);
    }

    /**
     * Deletes all deprecation warnings.
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn’t have access to the Deprecation Warnings utility
     */
    public function actionDeleteAllDeprecationErrors(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        Craft::$app->deprecator->deleteAllLogs();

        return $this->asSuccess();
    }

    /**
     * Deletes a deprecation error.
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn’t have access to the Deprecation Warnings utility
     */
    public function actionDeleteDeprecationError(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $logId = $this->request->getRequiredBodyParam('logId');
        Craft::$app->deprecator->deleteLogById($logId);

        return $this->asSuccess();
    }

    /**
     * Performs a Clear Caches action
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn’t have access to the Clear Caches utility
     * @throws BadRequestHttpException
     */
    public function actionClearCachesPerformAction(): Response
    {
        $caches = $this->request->getRequiredBodyParam('caches');

        foreach (ClearCaches::cacheOptions() as $cacheOption) {
            if (is_array($caches) && !in_array($cacheOption['key'], $caches, true)) {
                continue;
            }

            $action = $cacheOption['action'];

            if (is_string($action)) {
                try {
                    FileHelper::clearDirectory($action);
                } catch (InvalidArgumentException) {
                    // the directory doesn't exist
                } catch (Throwable $e) {
                    Craft::warning("Could not clear the directory $action: " . $e->getMessage(), __METHOD__);
                }
            } elseif (isset($cacheOption['params'])) {
                call_user_func_array($action, $cacheOption['params']);
            } else {
                $action();
            }
        }

        return $this->asSuccess();
    }

    /**
     * Performs an Invalidate Data Caches action.
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn’t have access to the Clear Caches utility
     * @throws BadRequestHttpException
     * @since 3.5.0
     */
    public function actionInvalidateTags(): Response
    {
        $tags = $this->request->getRequiredBodyParam('tags');
        $cache = Craft::$app->getCache();

        foreach ($tags as $tag) {
            TagDependency::invalidate($cache, $tag);
        }

        return $this->asSuccess();
    }

    /**
     * Performs a DB Backup action
     *
     * @return Response|null
     * @throws ForbiddenHttpException if the user doesn’t have access to the DB Backup utility
     * @throws Exception if the backup could not be created
     */
    public function actionDbBackupPerformAction(): ?Response
    {
        try {
            $backupPath = Craft::$app->getDb()->backup();
        } catch (Throwable $e) {
            throw new Exception('Could not create backup: ' . $e->getMessage());
        }

        if (!is_file($backupPath)) {
            throw new Exception("Could not create backup: the backup file doesn't exist.");
        }

        // Zip it up and delete the SQL file
        $zipPath = FileHelper::zip($backupPath);
        unlink($backupPath);

        if (!$this->request->getBodyParam('downloadBackup')) {
            return $this->asSuccess();
        }

        return $this->response->sendFile($zipPath, null, [
            'mimeType' => 'application/zip',
        ]);
    }

    /**
     * Performs a Find And Replace action
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn’t have access to the Find and Replace utility
     */
    public function actionFindAndReplacePerformAction(): Response
    {
        $params = $this->request->getRequiredBodyParam('params');

        if (!empty($params['find']) && !empty($params['replace'])) {
            Queue::push(new FindAndReplace([
                'find' => $params['find'],
                'replace' => $params['replace'],
            ]));
        }

        return $this->asSuccess();
    }

    /**
     * Applies new migrations
     *
     * @return Response
     * @throws ForbiddenHttpException if the user doesn’t have access to the Migrations utility
     */
    public function actionApplyNewMigrations(): Response
    {
        $migrator = Craft::$app->getContentMigrator();

        try {
            $migrator->up();
            $this->setSuccessFlash(Craft::t('app', 'Applied new migrations successfully.'));
        } catch (MigrationException) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t apply new migrations.'));
        }

        return $this->redirect('utilities/migrations');
    }

    /**
     * Returns info about all of the utilities.
     *
     * @return array
     */
    private function _utilityInfo(): array
    {
        $info = [];

        foreach (Craft::$app->getUtilities()->getAuthorizedUtilityTypes() as $class) {
            /** @var class-string<UtilityInterface> $class */
            $info[] = [
                'id' => $class::id(),
                'iconSvg' => $this->_getUtilityIconSvg($class),
                'displayName' => $class::displayName(),
                'iconPath' => $class::icon(),
                'badgeCount' => $class::badgeCount(),
            ];
        }

        return $info;
    }

    /**
     * Returns a utility type’s SVG icon.
     *
     * @param class-string<UtilityInterface> $class
     * @return string
     */
    private function _getUtilityIconSvg(string $class): string
    {
        $icon = $class::icon();

        if ($icon === null) {
            return $this->_getDefaultUtilityIconSvg($class);
        }

        try {
            $svg = Cp::iconSvg($icon);
            if ($svg !== '') {
                return $svg;
            }
        } catch (InvalidArgumentException) {
        }

        return $this->_getDefaultUtilityIconSvg($class);
    }

    /**
     * Returns the default icon SVG for a given utility type.
     *
     * @param class-string<UtilityInterface> $class
     * @return string
     */
    private function _getDefaultUtilityIconSvg(string $class): string
    {
        return $this->getView()->renderTemplate('_includes/fallback-icon.svg.twig', [
            'label' => $class::displayName(),
        ]);
    }
}
