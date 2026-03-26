<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use Craft;
use craft\errors\UploadFailedException;
use craft\helpers\Db;
use craft\web\UploadedFile;
use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Concerns\EnforcesVolumePermissions;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Translation\Formatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

readonly class UploadController
{
    use EnforcesVolumePermissions;
    use RespondsWithFlash;

    public function __construct(
        private Assets $assets,
        private Folders $folders,
        private Fields $fields,
    ) {}

    public function upload(Request $request): Response
    {
        $elementsService = Craft::$app->getElements();
        $uploadedFile = UploadedFile::getInstanceByName('assets-upload');

        abort_if(! $uploadedFile, 400, 'No file was uploaded');

        $folderId = $request->integer('folderId') ?: null;
        $fieldId = $request->integer('fieldId') ?: null;

        abort_if(! $folderId && ! $fieldId, 400, 'No target destination provided for uploading');

        $tempPath = $this->getUploadedFileTempPath($uploadedFile);

        if (empty($folderId)) {
            /** @var AssetsField|null $field */
            $field = $this->fields->getFieldById($fieldId);

            abort_if(! $field instanceof AssetsField, 400, 'The field provided is not an Assets field');

            if ($elementId = $request->input('elementId')) {
                $siteId = $request->input('siteId') ?: null;
                $element = $elementsService->getElementById($elementId, null, $siteId);
            } else {
                $element = null;
            }
            $folderId = $field->resolveDynamicPathToFolderId($element);

            $selectionCondition = $field->getSelectionCondition();
            if ($selectionCondition instanceof ElementCondition) {
                $selectionCondition->referenceElement = $element;
            }
        } else {
            $selectionCondition = null;
        }

        abort_if(empty($folderId), 400, 'The target destination provided for uploading is not valid');

        $folder = $this->folders->findFolder(['id' => $folderId]);

        abort_if(! $folder, 400, 'The target folder provided for uploading is not valid');

        // Check the permissions to upload in the resolved folder.
        $this->requireVolumePermissionByFolder('saveAssets', $folder);

        $filename = AssetsHelper::prepareAssetName($uploadedFile->name);

        if ($selectionCondition) {
            $tempFolder = $this->assets->getUserTemporaryUploadFolder();

            if ($folder->id !== $tempFolder->id) {
                // upload to the user's temp folder initially, with a temp name
                $originalFolder = $folder;
                $originalFilename = $filename;
                $folder = $tempFolder;
                $filename = uniqid('asset', true).'.'.pathinfo($filename, PATHINFO_EXTENSION);
            }
        }

        $asset = new Asset;
        $asset->tempFilePath = $tempPath;
        $asset->setFilename($filename);
        $asset->setMimeType(File::getMimeType($tempPath, checkExtension: false) ?? $uploadedFile->type);
        $asset->newFolderId = $folder->id;
        $asset->setVolumeId($folder->volumeId);
        $asset->uploaderId = $request->user()->id;
        $asset->avoidFilenameConflicts = true;

        if (isset($originalFilename)) {
            $asset->title = AssetsHelper::filename2Title(pathinfo($originalFilename, PATHINFO_FILENAME));
        }

        $asset->setScenario(Asset::SCENARIO_CREATE);
        $result = $elementsService->saveElement($asset);

        // In case of error, let user know about it.
        if (! $result) {
            return $this->asModelFailure($asset);
        }

        if ($selectionCondition) {
            if (! $selectionCondition->matchElement($asset)) {
                // delete and reject it
                $elementsService->deleteElement($asset, true);

                return $this->asFailure(t('{filename} isn’t selectable for this field.', [
                    'filename' => $uploadedFile->name,
                ]));
            }

            if (isset($originalFilename, $originalFolder)) {
                // move it into the original target destination
                $asset->newFilename = $originalFilename;
                $asset->newFolderId = $originalFolder->id;
                $asset->setScenario(Asset::SCENARIO_MOVE);

                if (! $elementsService->saveElement($asset)) {
                    return $this->asModelFailure($asset);
                }
            }
        }

        // try to get uploaded asset's URL
        $url = null;
        try {
            $url = $asset->getUrl();
        } catch (Throwable) {
            // do nothing
        }

        if ($asset->conflictingFilename !== null) {
            $conflictingAsset = Asset::findOne(['folderId' => $folder->id, 'filename' => $asset->conflictingFilename]);

            return new JsonResponse([
                'conflict' => t('A file with the name "{filename}" already exists.', ['filename' => $asset->conflictingFilename]),
                'assetId' => $asset->id,
                'filename' => $asset->conflictingFilename,
                'conflictingAssetId' => $conflictingAsset->id ?? null,
                'suggestedFilename' => $asset->suggestedFilename,
                'conflictingAssetUrl' => ($conflictingAsset && $conflictingAsset->getVolume()->getFs()->hasUrls) ? $conflictingAsset->getUrl() : null,
                'url' => $url,
            ]);
        }

        return $this->asSuccess(data: [
            'filename' => $asset->getFilename(),
            'assetId' => $asset->id,
            'url' => $url,
        ]);
    }

    public function replaceFile(Request $request): Response
    {
        $assetId = $request->input('assetId');
        $sourceAssetId = $request->input('sourceAssetId');
        $targetFilename = $request->input('targetFilename');

        if (
            $targetFilename &&
            (str_contains((string) $targetFilename, '/') || str_contains((string) $targetFilename, '\\'))
        ) {
            abort(400, 'Invalid filename: $targetFilename');
        }

        $uploadedFile = UploadedFile::getInstanceByName('replaceFile');

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

        $this->requireVolumePermissionByAsset('replaceFiles', $assetToReplace ?: $sourceAsset);
        $this->requirePeerVolumePermissionByAsset('replacePeerFiles', $assetToReplace ?: $sourceAsset);

        // Handle the Element Action
        if ($assetToReplace !== null && $uploadedFile) {
            $tempPath = $this->getUploadedFileTempPath($uploadedFile);
            $filename = AssetsHelper::prepareAssetName($uploadedFile->name);
            $this->assets->replaceAssetFile($assetToReplace, $tempPath, $filename, $uploadedFile->type);
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
                    ->filename(Db::escapeParam($targetFilename))
                    ->one();
            }

            if (! empty($assetToReplace)) {
                $tempPath = $sourceAsset->getCopyOfFile();
                $this->assets->replaceAssetFile($assetToReplace, $tempPath, $assetToReplace->getFilename(), $sourceAsset->getMimeType());
                Craft::$app->getElements()->deleteElement($sourceAsset);
            } else {
                $volume = $sourceAsset->getVolume();
                $volume->sourceDisk()->delete(rtrim((string) $sourceAsset->folderPath, '/').'/'.$targetFilename);
                $sourceAsset->newFilename = $targetFilename;
                Craft::$app->getElements()->saveElement($sourceAsset);
                $assetId = $sourceAsset->id;
            }
        }

        $resultingAsset = $assetToReplace ?: $sourceAsset;

        return $this->asSuccess(data: [
            'assetId' => $assetId,
            'filename' => $resultingAsset->getFilename(),
            'formattedSize' => $resultingAsset->getFormattedSize(0),
            'formattedSizeInBytes' => $resultingAsset->getFormattedSizeInBytes(false),
            'formattedDateUpdated' => I18N::getFormatter()->asDatetime($resultingAsset->dateUpdated, Formatter::FORMAT_WIDTH_SHORT),
            'dimensions' => $resultingAsset->getDimensions(),
            'updatedTimestamp' => $resultingAsset->dateUpdated->getTimestamp(),
            'resultingUrl' => $resultingAsset->getUrl(),
        ]);
    }

    /**
     * @throws UploadFailedException
     */
    private function getUploadedFileTempPath(UploadedFile $uploadedFile): string
    {
        if ($uploadedFile->getHasError()) {
            throw new UploadFailedException($uploadedFile->error);
        }

        // Make sure the file extension is allowed
        $allowedExtensions = Cms::config()->allowedFileExtensions;
        $extension = strtolower(pathinfo($uploadedFile->name, PATHINFO_EXTENSION));

        if (! in_array($extension, $allowedExtensions, true)) {
            throw new AssetDisallowedExtensionException(t('"{extension}" is not an allowed file extension.', [
                'extension' => $extension,
            ]));
        }

        // Move the uploaded file to the temp folder
        $tempPath = $uploadedFile->saveAsTempFile();

        if ($tempPath === false) {
            throw new UploadFailedException(UPLOAD_ERR_CANT_WRITE);
        }

        return $tempPath;
    }
}
