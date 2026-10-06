<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Data\AssetIngest;
use CraftCms\Cms\Asset\Data\AssetIngestResult;
use CraftCms\Cms\Asset\Data\UploadResult;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\AssetIngestStatus;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Image\Data\ImageColors;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Translation\Formatter;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class AssetUploadHandler
{
    public function __construct(
        private Assets $assets,
        private Folders $folders,
        private Fields $fields,
        private Elements $elements,
    ) {}

    /**
     * @param  array<string, mixed>  $parameters
     * @return array{VolumeFolder, ElementConditionInterface|null}
     */
    public function resolveTarget(array $parameters, bool $authorizedGuest = false): array
    {
        $folderId = (int) ($parameters['folderId'] ?? 0) ?: null;
        $fieldId = (int) ($parameters['fieldId'] ?? 0) ?: null;

        abort_if(! $folderId && ! $fieldId, 400, 'No target destination provided for uploading');

        if (empty($folderId)) {
            /** @var AssetsField|null $field */
            $field = $this->fields->getFieldById($fieldId);

            abort_if(! $field instanceof AssetsField, 400, 'The field provided is not an Assets field');

            if ($elementId = (int) ($parameters['elementId'] ?? 0)) {
                $siteId = (int) ($parameters['siteId'] ?? 0) ?: null;
                $element = $this->elements->getElementById($elementId, null, $siteId);
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

        if (! $authorizedGuest) {
            Gate::authorize('uploadAsset', $folder);
        }

        return [$folder, $selectionCondition];
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  ImageColors|null  $colors  Colors the uploader already sampled from the file
     */
    public function store(
        array $parameters,
        UploadedFile $file,
        bool $authorizedGuest = false,
        ?int $uploaderId = null,
        ?ImageColors $colors = null,
    ): UploadResult {
        [$folder, $selectionCondition] = $this->resolveTarget($parameters, $authorizedGuest);

        $result = $this->ingest(new AssetIngest(
            source: $file,
            filename: $file->filename,
            mimeType: $file->mimeType(),
            folder: $folder,
            sanitizeOnUpload: $authorizedGuest || ! request()->isCpRequest() || Cms::config()->sanitizeCpImageUploads,
            selectionCondition: $selectionCondition,
            temporaryFolder: $selectionCondition ? $this->assets->getUserTemporaryUploadFolder() : null,
            colors: $colors,
            uploaderId: $uploaderId,
        ));

        return $this->uploadResult($result);
    }

    public function ingest(AssetIngest $ingest): AssetIngestResult
    {
        try {
            return $this->performIngest($ingest);
        } finally {
            $ingest->source->release();
        }
    }

    private function performIngest(AssetIngest $ingest): AssetIngestResult
    {
        $asset = $ingest->asset ?? new Asset;

        if ($asset->id !== null) {
            throw new InvalidArgumentException('Asset ingest requires an unsaved asset.');
        }

        $folder = $ingest->folder;
        $validTarget = false;

        if ($folder->id !== null && ($persistedFolder = $this->folders->getFolderById($folder->id)) !== null) {
            $folder = $persistedFolder;
            $validTarget = true;
        }

        $filename = AssetsHelper::prepareAssetName($ingest->filename);
        $moveAfterSelection = false;

        if ($validTarget && $ingest->selectionCondition && $ingest->temporaryFolder) {
            $tempFolder = $ingest->temporaryFolder;

            if ($folder->id !== $tempFolder->id) {
                // upload to the user's temp folder initially, with a temp name
                $destinationFolder = $folder;
                $destinationFilename = $filename;
                $folder = $tempFolder;
                $filename = uniqid('asset', true).'.'.pathinfo($filename, PATHINFO_EXTENSION);
                $moveAfterSelection = true;
            }
        }

        $asset->uploadSource = $ingest->source;
        $asset->uploadColors = $ingest->colors;
        $asset->sanitizeOnUpload = $ingest->sanitizeOnUpload;
        $asset->setFilename($filename);
        $asset->setMimeType($ingest->mimeType);
        $asset->newFolderId = $folder->id;
        $asset->setVolumeId($folder->volumeId);
        $asset->uploaderId = $ingest->uploaderId;
        $asset->avoidFilenameConflicts = true;

        if ($moveAfterSelection && ! $asset->title) {
            $asset->title = AssetsHelper::filename2Title(pathinfo($destinationFilename, PATHINFO_FILENAME));
        }

        $asset->ruleset->useScenario(AssetRules::SCENARIO_CREATE);

        if (! $validTarget) {
            $asset->errors()->add('newLocation', t('The target folder provided for uploading is not valid.'));

            return new AssetIngestResult(AssetIngestStatus::Invalid, $asset);
        }

        if (! $this->elements->saveElement($asset)) {
            return new AssetIngestResult(AssetIngestStatus::Invalid, $asset);
        }

        if ($ingest->selectionCondition) {
            if (! $ingest->selectionCondition->matchElement($asset)) {
                $this->elements->deleteElement($asset, true);

                return new AssetIngestResult(
                    AssetIngestStatus::Rejected,
                    $asset,
                    message: t('{filename} isn’t selectable for this field.', [
                        'filename' => $ingest->filename,
                    ]),
                );
            }

            if ($moveAfterSelection) {
                $asset->newFilename = $destinationFilename;
                $asset->newFolderId = $destinationFolder->id;
                $asset->ruleset->useScenario(AssetRules::SCENARIO_MOVE);

                if (! $this->elements->saveElement($asset)) {
                    return new AssetIngestResult(AssetIngestStatus::Invalid, $asset);
                }
            }
        }

        $conflictingAsset = $asset->conflictingFilename !== null
            ? Asset::findOne(['folderId' => $asset->folderId, 'filename' => $asset->conflictingFilename])
            : null;

        return new AssetIngestResult(AssetIngestStatus::Saved, $asset, $conflictingAsset);
    }

    private function uploadResult(AssetIngestResult $result): UploadResult
    {
        if ($result->status === AssetIngestStatus::Invalid) {
            return $this->failure($result->asset);
        }

        if ($result->status === AssetIngestStatus::Rejected) {
            return new UploadResult(['message' => $result->message], 400);
        }

        $asset = $result->asset;

        // try to get uploaded asset's URL
        $url = null;
        try {
            $url = $asset->getUrl();
        } catch (Throwable) {
            // do nothing
        }

        if ($asset->conflictingFilename !== null) {
            $conflictingAsset = $result->conflictingAsset;

            return new UploadResult([
                'conflict' => t('A file with the name “{filename}” already exists.', ['filename' => $asset->conflictingFilename]),
                'assetId' => $asset->id,
                'filename' => $asset->conflictingFilename,
                'conflictingAssetId' => $conflictingAsset->id ?? null,
                'suggestedFilename' => $asset->suggestedFilename,
                'conflictingAssetUrl' => ($conflictingAsset && $conflictingAsset->getVolume()->sourceHasUrls()) ? $conflictingAsset->getUrl() : null,
                'url' => $url,
            ]);
        }

        return new UploadResult([
            'filename' => $asset->getFilename(),
            'assetId' => $asset->id,
            'url' => $url,
        ]);
    }

    /**
     * @param  ImageColors|null  $colors  Colors the uploader already sampled from the file
     */
    public function replace(int $assetId, UploadedFile $file, ?ImageColors $colors = null): UploadResult
    {
        $asset = $this->assets->getAssetById($assetId);
        abort_unless($asset !== null, 404, 'Asset not found.');
        Gate::authorize('replaceFile', $asset);

        $asset->uploadSource = $file;
        $asset->uploadColors = $colors;
        $this->assets->replaceAssetFile($asset, $file->localPath(), $file->filename, $file->mimeType());

        if ($asset->errors()->isNotEmpty()) {
            return $this->failure($asset);
        }

        return $this->replacementResult($asset, $asset->id);
    }

    public function replacementResult(Asset $resultingAsset, int $assetId): UploadResult
    {
        return new UploadResult([
            'assetId' => $assetId,
            'filename' => $resultingAsset->getFilename(),
            'formattedSize' => $resultingAsset->getFormattedSize(0),
            'formattedSizeInBytes' => $resultingAsset->getFormattedSizeInBytes(false),
            'formattedDateUpdated' => I18N::getFormatter()->asDatetime(
                $resultingAsset->dateUpdated,
                Formatter::FORMAT_WIDTH_SHORT,
                true,
            ),
            'dimensions' => $resultingAsset->getDimensions(),
            'updatedTimestamp' => $resultingAsset->dateUpdated->getTimestamp(),
            'resultingUrl' => $resultingAsset->getUrl(),
        ]);
    }

    private function failure(Asset $asset): UploadResult
    {
        return new UploadResult([
            'modelName' => 'model',
            'modelClass' => $asset::class,
            'model' => Arr::toArray($asset),
            'errors' => $asset->errors()->getMessages(),
        ], 400);
    }
}
