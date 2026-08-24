<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Asset\AssetIndexer;
use CraftCms\Cms\Asset\AssetProcessors;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\AssetIndexes;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class AssetIndexesController
{
    use RespondsWithFlash;

    public function __construct(
        private AssetIndexer $assetIndexer,
        private AssetProcessors $assetProcessors,
        Utilities $utilitiesService,
    ) {
        if (! $utilitiesService->checkAuthorization(AssetIndexes::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function startIndexing(Request $request): Response
    {
        $validated = $request->validate([
            'volumes' => ['required', 'array'],
            'cacheImages' => ['nullable', 'boolean'],
            'listEmptyFolders' => ['nullable', 'boolean'],
        ]);

        $volumeIds = array_map(fn ($volumeId) => (int) $volumeId, $validated['volumes']);
        $cacheRemoteImages = (bool) ($validated['cacheImages'] ?? false);
        $listEmptyFolders = (bool) ($validated['listEmptyFolders'] ?? false);

        // Filter out any disallowed volumes
        $allowedVolumeIds = array_map(fn ($volume) => $volume->id, AssetIndexes::volumes());
        $volumeIds = array_intersect($volumeIds, $allowedVolumeIds);

        if (empty($volumeIds)) {
            return $this->asFailure(t('No volumes specified.'));
        }

        $indexingSession = $this->assetIndexer->startIndexingSession($volumeIds, $cacheRemoteImages, $listEmptyFolders);

        $data = ['session' => $indexingSession];
        $error = null;

        if ($indexingSession->totalEntries === 0 && ! $indexingSession->processIfRootEmpty) {
            $data['stop'] = $indexingSession->id;
            $error = t('The filesystem doesn’t contain any files.');
            $this->assetIndexer->stopIndexingSession($indexingSession);
        }

        return $error ?
            $this->asFailure($error, $data) :
            $this->asSuccess(null, $data);
    }

    public function stopIndexingSession(Request $request): Response
    {
        $validated = $request->validate([
            'sessionId' => ['required', 'integer'],
        ]);

        $sessionId = (int) $validated['sessionId'];

        if (empty($sessionId)) {
            return $this->asFailure(t('No indexing session specified.'));
        }

        $session = $this->assetIndexer->getIndexingSessionById($sessionId);

        if ($session) {
            $this->assetIndexer->stopIndexingSession($session);
        }

        return $this->asSuccess(null, ['stop' => $sessionId]);
    }

    public function processIndexingSession(Request $request): Response
    {
        $validated = $request->validate([
            'sessionId' => ['required', 'integer'],
        ]);

        $sessionId = (int) $validated['sessionId'];

        if (empty($sessionId)) {
            return $this->asFailure(t('No indexing session specified.'));
        }

        $indexingSession = $this->assetIndexer->getIndexingSessionById($sessionId);

        // Have to account for the fact that some people might be processing this in parallel
        // If the indexing session no longer exists - most likely a parallel user finished it
        if (! $indexingSession) {
            return $this->asSuccess(null, ['stop' => $sessionId]);
        }

        $skipDialog = false;

        // If action is not required, continue with indexing
        if (! $indexingSession->actionRequired) {
            $indexingSession = $this->assetIndexer->processIndexSession($indexingSession);

            if ($indexingSession->forceStop) {
                $this->assetIndexer->stopIndexingSession($indexingSession);

                return $this->asFailure(null, [
                    'stop' => $sessionId,
                    'message' => t('There was a problem indexing assets.'),
                ]);
            }

            // If action is now required, we just processed the last entry
            // To save a round-trip, just pull the session review data
            if ($indexingSession->actionRequired) {
                $indexingSession->skippedEntries = $this->assetIndexer->getSkippedItemsForSession($indexingSession);
                $indexingSession->missingEntries = $this->assetIndexer->getMissingEntriesForSession($indexingSession);

                // If nothing out of ordinary, just end it.
                if (
                    empty($indexingSession->skippedEntries) &&
                    empty($indexingSession->missingEntries['folders']) &&
                    empty($indexingSession->missingEntries['files'])
                ) {
                    $this->assetIndexer->stopIndexingSession($indexingSession);

                    return $this->asSuccess(null, ['stop' => $sessionId]);
                }
            }
        } else {
            $skipDialog = true;
        }

        return $this->asSuccess(null, ['session' => $indexingSession, 'skipDialog' => $skipDialog]);
    }

    public function indexingSessionOverview(Request $request): Response
    {
        $validated = $request->validate([
            'sessionId' => ['required', 'integer'],
        ]);

        $sessionId = (int) $validated['sessionId'];

        if (empty($sessionId)) {
            return $this->asFailure(t('No indexing session specified.'));
        }

        $indexingSession = $this->assetIndexer->getIndexingSessionById($sessionId);

        if (! $indexingSession || ! $indexingSession->actionRequired) {
            return $this->asFailure(t("Cannot find the indexing session, or there\u{2019}s nothing to review."));
        }

        $indexingSession->skippedEntries = $this->assetIndexer->getSkippedItemsForSession($indexingSession);
        $indexingSession->missingEntries = $this->assetIndexer->getMissingEntriesForSession($indexingSession);

        return $this->asSuccess(null, ['session' => $indexingSession]);
    }

    public function finishIndexingSession(Request $request, Elements $elements): Response
    {
        $validated = $request->validate([
            'sessionId' => ['required', 'integer'],
            'deleteFolder' => ['nullable', 'array'],
            'deleteAsset' => ['nullable', 'array'],
        ]);

        $sessionId = (int) $validated['sessionId'];

        if (empty($sessionId)) {
            return $this->asFailure(t('No indexing session specified.'));
        }

        $session = $this->assetIndexer->getIndexingSessionById($sessionId);

        if ($session) {
            $this->assetIndexer->stopIndexingSession($session);
        }

        $deleteFolders = $validated['deleteFolder'] ?? [];
        $deleteFiles = $validated['deleteAsset'] ?? [];

        if (! empty($deleteFolders)) {
            // if listEmptyFolders was set to true, delete the directories too, so that they don't pop back up on next indexing
            Folders::deleteFoldersByIds($deleteFolders, $session->listEmptyFolders ?? false);
        }

        if (! empty($deleteFiles)) {
            /** @var Asset[] $assets */
            $assets = Asset::find()
                ->status(null)
                ->id($deleteFiles)
                ->all();

            foreach ($assets as $asset) {
                $this->assetProcessors->invalidate($asset);
                $asset->keepFileOnDelete = true;
                $elements->deleteElement($asset);
            }
        }

        return $this->asSuccess(null, ['stop' => $sessionId]);
    }
}
