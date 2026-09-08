<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\AssetUploadHandler;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Query;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

readonly class ResolveUploadConflictController
{
    use RespondsWithFlash;

    public function __construct(
        private Assets $assets,
        private AssetUploadHandler $uploads,
        private Elements $elements,
    ) {}

    public function __invoke(Request $request): Response
    {
        $request->validate([
            'sourceAssetId' => ['required', 'integer', 'min:1'],
            'assetId' => ['nullable', 'integer', 'min:1', 'different:sourceAssetId'],
            'targetFilename' => ['required_without:assetId', 'nullable', 'string', 'max:255'],
        ]);

        $sourceAsset = $this->assets->getAssetById($request->integer('sourceAssetId'));
        abort_unless($sourceAsset !== null, 404, 'Asset not found.');
        Gate::authorize('replaceFile', $sourceAsset);
        $targetFilename = $request->input('targetFilename');

        if ($assetId = $request->integer('assetId')) {
            $assetToReplace = $this->assets->getAssetById($assetId);
            abort_unless($assetToReplace !== null, 404, 'Asset not found.');
        } else {
            abort_if(str_contains($targetFilename, '/') || str_contains($targetFilename, '\\'), 422, 'Invalid filename.');
            abort_unless(pathinfo($targetFilename, PATHINFO_EXTENSION) === $sourceAsset->getExtension(), 422, 'The filename must retain the original file extension.');

            $assetToReplace = Asset::find()
                ->folderId($sourceAsset->folderId)
                ->filename(Query::escapeParam($targetFilename))
                ->one();
        }

        if ($assetToReplace !== null) {
            abort_if($assetToReplace->id === $sourceAsset->id, 422, 'An asset cannot replace itself.');
            Gate::authorize('replaceFile', $assetToReplace);
            $this->assets->replaceAssetFile($assetToReplace, $sourceAsset->getCopyOfFile(), $assetToReplace->getFilename(), $sourceAsset->getMimeType());

            if ($assetToReplace->errors()->isNotEmpty()) {
                return $this->asModelFailure($assetToReplace);
            }

            $this->elements->deleteElement($sourceAsset);
        } else {
            $sourceAsset->getVolume()->sourceDisk()->delete(rtrim((string) $sourceAsset->folderPath, '/').'/'.$targetFilename);
            $sourceAsset->newFilename = $targetFilename;

            if (! $this->elements->saveElement($sourceAsset)) {
                return $this->asModelFailure($sourceAsset);
            }
        }

        $resultingAsset = $assetToReplace ?? $sourceAsset;

        return new JsonResponse($this->uploads->replacementResult($resultingAsset, $resultingAsset->id)->payload());
    }
}
