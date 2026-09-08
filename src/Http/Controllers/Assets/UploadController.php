<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\AssetUploadHandler;
use CraftCms\Cms\Asset\Data\UploadResult;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException;
use CraftCms\Cms\Asset\Exceptions\UploadFailedException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Query;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

readonly class UploadController
{
    use RespondsWithFlash;

    public function __construct(
        private Assets $assets,
        private AssetUploadHandler $uploads,
        private Elements $elements,
    ) {}

    public function upload(Request $request): Response
    {
        $uploadedFile = $request->file('assets-upload');

        abort_if(is_array($uploadedFile), 400, 'Exactly one file must be uploaded');

        abort_if(! $uploadedFile, 400, 'No file was uploaded');

        $tempPath = $this->getUploadedFileTempPath($uploadedFile);

        return $this->respond($this->uploads->store(
            $request->all(),
            $uploadedFile->getClientOriginalName(),
            $uploadedFile->getClientMimeType(),
            $tempPath,
            uploaderId: $request->craftUser()?->getCraftUserId(),
        ));
    }

    public function replaceFile(Request $request): Response
    {
        $assetId = $request->integer('assetId');
        $sourceAssetId = $request->integer('sourceAssetId');
        $targetFilename = $request->input('targetFilename');

        if (
            $targetFilename &&
            (str_contains((string) $targetFilename, '/') || str_contains((string) $targetFilename, '\\'))
        ) {
            abort(400, 'Invalid filename: $targetFilename');
        }

        $uploadedFile = $request->file('replaceFile');

        if (is_array($uploadedFile)) {
            abort(400, 'Exactly one file must be uploaded.');
        }

        // Must have at least one existing asset (source or target).
        // Must have either target asset or target filename.
        // Must have either uploaded file or source asset.
        if ((empty($assetId) && empty($sourceAssetId)) ||
            (empty($assetId) && empty($targetFilename)) ||
            ($uploadedFile === null && empty($sourceAssetId))
        ) {
            abort(400, 'Incorrect combination of parameters.');
        }

        $sourceAsset = null;
        $assetToReplace = null;

        if ($assetId && ! $assetToReplace = $this->assets->getAssetById($assetId)) {
            abort(404, 'Asset not found.');
        }

        if ($sourceAssetId && ! $sourceAsset = $this->assets->getAssetById($sourceAssetId)) {
            abort(404, 'Asset not found.');
        }

        if ($assetToReplace) {
            Gate::authorize('replaceFile', $assetToReplace);
        }

        if ($sourceAsset) {
            Gate::authorize('replaceFile', $sourceAsset);
        }

        // Handle the Element Action
        if ($assetToReplace !== null && $uploadedFile) {
            $tempPath = $this->getUploadedFileTempPath($uploadedFile);
            $filename = AssetsHelper::prepareAssetName($uploadedFile->getClientOriginalName());
            $this->assets->replaceAssetFile($assetToReplace, $tempPath, $filename, $uploadedFile->getClientMimeType());
        } elseif ($sourceAsset !== null) {
            // Or replace using an existing Asset
            if ($assetToReplace === null) {
                // Make sure the extension didn't change
                if (pathinfo((string) $targetFilename, PATHINFO_EXTENSION) !== $sourceAsset->getExtension()) {
                    abort(400, $targetFilename.' doesn\'t have the original file extension.');
                }

                /** @var Asset|null $assetToReplace */
                $assetToReplace = Asset::find()
                    ->select(['elements.id'])
                    ->folderId($sourceAsset->folderId)
                    ->filename(Query::escapeParam($targetFilename))
                    ->one();

                if ($assetToReplace) {
                    Gate::authorize('replaceFile', $assetToReplace);
                }
            }

            if (! empty($assetToReplace)) {
                $tempPath = $sourceAsset->getCopyOfFile();
                $this->assets->replaceAssetFile($assetToReplace, $tempPath, $assetToReplace->getFilename(), $sourceAsset->getMimeType());
                $this->elements->deleteElement($sourceAsset);
            } else {
                $volume = $sourceAsset->getVolume();
                $volume->sourceDisk()->delete(rtrim((string) $sourceAsset->folderPath, '/').'/'.$targetFilename);
                $sourceAsset->newFilename = $targetFilename;
                $this->elements->saveElement($sourceAsset);
                $assetId = $sourceAsset->id;
            }
        }

        $resultingAsset = $assetToReplace ?: $sourceAsset;

        return $this->respond($this->uploads->replacementResult($resultingAsset, $assetId));
    }

    private function respond(UploadResult $result): Response
    {
        if ($result->status !== 200) {
            return $this->asFailure($result->message ?? null, $result->payload());
        }

        if (isset($result->conflict)) {
            return new JsonResponse($result->payload());
        }

        return $this->asSuccess(data: $result->payload());
    }

    /**
     * @throws UploadFailedException
     */
    private function getUploadedFileTempPath(UploadedFile $uploadedFile): string
    {
        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new UploadFailedException($uploadedFile->getError());
        }

        // Make sure the file extension is allowed
        $allowedExtensions = Cms::config()->allowedFileExtensions;
        $extension = strtolower(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_EXTENSION));

        if (! in_array($extension, $allowedExtensions, true)) {
            throw new AssetDisallowedExtensionException(t('“{extension}” is not an allowed file extension.', [
                'extension' => $extension,
            ]));
        }

        // Move the uploaded file to the temp folder
        $tempPath = $this->saveAsTempFile($uploadedFile);

        if ($tempPath === false) {
            throw new UploadFailedException(UPLOAD_ERR_CANT_WRITE);
        }

        return $tempPath;
    }

    private function saveAsTempFile(UploadedFile $uploadedFile): string|false
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'craft-upload-');

        if ($tempPath === false) {
            return false;
        }

        try {
            $uploadedFile->move(dirname($tempPath), basename($tempPath));
        } catch (Throwable) {
            File::delete($tempPath);

            return false;
        }

        return $tempPath;
    }
}
