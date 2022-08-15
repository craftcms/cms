<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\Asset;
use craft\errors\AssetException;
use craft\helpers\Json;
use craft\i18n\Locale;
use craft\models\AssetIndexingSession;
use craft\web\Controller;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The AssetIndexes class is a controller that handles asset indexing tasks.
 * Note that all actions in the controller require an authenticated Craft session as well as the relevant permissions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AssetIndexesController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // No permission no bueno
        $this->requirePermission('utility:asset-indexes');
        $this->requireAcceptsJson();

        return parent::beforeAction($action);
    }

    /**
     * Start an indexing session.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionStartIndexing(): Response
    {
        $request = Craft::$app->getRequest();
        $volumes = (array)$request->getRequiredBodyParam('volumes');
        $cacheRemoteImages = (bool)$request->getBodyParam('cacheImages', false);

        if (empty($volumes)) {
            return $this->asFailure(Craft::t('app', 'No volumes specified.'));
        }

        $indexingSession = Craft::$app->getAssetIndexer()->startIndexingSession($volumes, $cacheRemoteImages);
        $sessionData = $this->prepareSessionData($indexingSession);

        $data = ['session' => $sessionData];
        $error = null;

        if ($indexingSession->totalEntries === 0) {
            $data['stop'] = $indexingSession->id;
            $error = Craft::t('app', 'Nothing to index.');
            Craft::$app->getAssetIndexer()->stopIndexingSession($indexingSession);
        }

        return $error ?
            $this->asFailure($error, $data) :
            $this->asSuccess(data: $data);
    }

    /**
     * Stop an indexing sessions.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws Throwable if something goes wrong.
     */
    public function actionStopIndexingSession(): Response
    {
        $sessionId = (int)Craft::$app->getRequest()->getRequiredBodyParam('sessionId');

        if (empty($sessionId)) {
            return $this->asFailure(Craft::t('app', 'No indexing session specified.'));
        }

        $session = Craft::$app->getAssetIndexer()->getIndexingSessionById($sessionId);

        if ($session) {
            Craft::$app->getAssetIndexer()->stopIndexingSession($session);
        }

        return $this->asSuccess(data: ['stop' => $sessionId]);
    }

    /**
     * Progress an indexing session by one step.
     *
     * @return Response
     * @throws Throwable if something goes wrong
     */
    public function actionProcessIndexingSession(): Response
    {
        $sessionId = (int)Craft::$app->getRequest()->getRequiredBodyParam('sessionId');

        if (empty($sessionId)) {
            return $this->asFailure(Craft::t('app', 'No indexing session specified.'));
        }

        $assetIndexer = Craft::$app->getAssetIndexer();
        $indexingSession = $assetIndexer->getIndexingSessionById($sessionId);

        // Have to account for the fact that some people might be processing this in parallel
        // If the indexing session no longer exists - most likely a parallel user finished it
        if (!$indexingSession) {
            return $this->asSuccess(data: ['stop' => $sessionId]);
        }

        $skipDialog = false;

        // If action is not required, continue with indexing
        if (!$indexingSession->actionRequired) {
            $indexingSession = $assetIndexer->processIndexSession($indexingSession);

            // If action is now required, we just processed the last entry
            // To save a round-trip, just pull the session review data
            if ($indexingSession->actionRequired) {
                $indexingSession->skippedEntries = $assetIndexer->getSkippedItemsForSession($indexingSession);
                $indexingSession->missingEntries = $assetIndexer->getMissingEntriesForSession($indexingSession);

                // If nothing out of ordinary, just end it.
                if (
                    empty($indexingSession->skippedEntries) &&
                    empty($indexingSession->missingEntries['folders']) &&
                    empty($indexingSession->missingEntries['files'])
                ) {
                    $assetIndexer->stopIndexingSession($indexingSession);
                    return $this->asSuccess(data: ['stop' => $sessionId]);
                }
            }
        } else {
            $skipDialog = true;
        }

        $sessionData = $this->prepareSessionData($indexingSession);
        return $this->asSuccess(data: ['session' => $sessionData, 'skipDialog' => $skipDialog]);
    }

    /**
     * Fetch an indexing session overview.
     *
     * @return Response
     * @throws AssetException
     * @throws BadRequestHttpException
     */
    public function actionIndexingSessionOverview(): Response
    {
        $sessionId = (int)Craft::$app->getRequest()->getRequiredBodyParam('sessionId');

        if (empty($sessionId)) {
            return $this->asFailure(Craft::t('app', 'No indexing session specified.'));
        }

        $assetIndexer = Craft::$app->getAssetIndexer();
        $indexingSession = $assetIndexer->getIndexingSessionById($sessionId);

        if (!$indexingSession || !$indexingSession->actionRequired) {
            return $this->asFailure(Craft::t('app', 'Cannot find the indexing session, or there’s nothing to review.'));
        }

        $indexingSession->skippedEntries = $assetIndexer->getSkippedItemsForSession($indexingSession);
        $indexingSession->missingEntries = $assetIndexer->getMissingEntriesForSession($indexingSession);

        $sessionData = $this->prepareSessionData($indexingSession);
        return $this->asSuccess(data: ['session' => $sessionData]);
    }

    /**
     * Finish an indexing session, removing the specified file and folder records.
     *
     * @return Response
     * @throws Throwable
     */
    public function actionFinishIndexingSession(): Response
    {
        $sessionId = (int)Craft::$app->getRequest()->getRequiredBodyParam('sessionId');

        if (empty($sessionId)) {
            return $this->asFailure(Craft::t('app', 'No indexing session specified.'));
        }

        $session = Craft::$app->getAssetIndexer()->getIndexingSessionById($sessionId);

        if ($session) {
            Craft::$app->getAssetIndexer()->stopIndexingSession($session);
        }

        $deleteFolders = Craft::$app->getRequest()->getBodyParam('deleteFolder', []);
        $deleteFiles = Craft::$app->getRequest()->getBodyParam('deleteAsset', []);

        if (!empty($deleteFolders)) {
            Craft::$app->getAssets()->deleteFoldersByIds($deleteFolders, false);
        }

        if (!empty($deleteFiles)) {
            /** @var Asset[] $assets */
            $assets = Asset::find()
                ->status(null)
                ->id($deleteFiles)
                ->all();

            foreach ($assets as $asset) {
                Craft::$app->getImageTransforms()->deleteCreatedTransformsForAsset($asset);
                $asset->keepFileOnDelete = true;
                Craft::$app->getElements()->deleteElement($asset);
            }
        }

        return $this->asSuccess(data: ['stop' => $sessionId]);
    }

    /**
     * Prepare session data for transport.
     *
     * @param AssetIndexingSession $indexingSession
     * @return array
     */
    private function prepareSessionData(AssetIndexingSession $indexingSession): array
    {
        $sessionData = $indexingSession->toArray();
        unset($sessionData['dateUpdated']);
        $sessionData['dateCreated'] = $indexingSession->dateUpdated->format(Craft::$app->getLocale()->getDateTimeFormat('medium', Locale::FORMAT_PHP));
        $sessionData['indexedVolumes'] = Json::decodeIfJson($indexingSession->indexedVolumes);
        return $sessionData;
    }
}
