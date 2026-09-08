<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Data\UploadedAssetFile;
use CraftCms\Cms\Asset\Data\UploadResult;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Translation\Formatter;
use Illuminate\Support\Facades\Gate;
use Throwable;

use function CraftCms\Cms\t;

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

    /** @param array<string, mixed> $parameters */
    public function store(
        array $parameters,
        string $originalName,
        string $mimeType,
        ?string $tempPath = null,
        ?UploadedAssetFile $source = null,
        bool $authorizedGuest = false,
        ?int $uploaderId = null,
    ): UploadResult {
        [$folder, $selectionCondition] = $this->resolveTarget($parameters, $authorizedGuest);

        $filename = AssetsHelper::prepareAssetName($originalName);

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
        $asset->uploadSource = $source;
        if ($authorizedGuest) {
            $asset->sanitizeOnUpload = true;
        }
        $asset->setFilename($filename);
        $asset->setMimeType($tempPath ? (File::getMimeType($tempPath, checkExtension: false) ?? $mimeType) : $mimeType);
        $asset->newFolderId = $folder->id;
        $asset->setVolumeId($folder->volumeId);
        $asset->uploaderId = $uploaderId;
        $asset->avoidFilenameConflicts = true;

        if (isset($originalFilename)) {
            $asset->title = AssetsHelper::filename2Title(pathinfo($originalFilename, PATHINFO_FILENAME));
        }

        $asset->ruleset->useScenario(AssetRules::SCENARIO_CREATE);
        $result = $this->elements->saveElement($asset);

        // In case of error, let user know about it.
        if (! $result) {
            return $this->failure($asset);
        }

        if ($selectionCondition) {
            if (! $selectionCondition->matchElement($asset)) {
                // delete and reject it
                $this->elements->deleteElement($asset, true);

                return new UploadResult(['message' => t('{filename} isn’t selectable for this field.', [
                    'filename' => $originalName,
                ])], 400);
            }

            if (isset($originalFilename, $originalFolder)) {
                // move it into the original target destination
                $asset->newFilename = $originalFilename;
                $asset->newFolderId = $originalFolder->id;
                $asset->ruleset->useScenario(AssetRules::SCENARIO_MOVE);

                if (! $this->elements->saveElement($asset)) {
                    return $this->failure($asset);
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

    public function replace(int $assetId, UploadedAssetFile $file): UploadResult
    {
        $asset = $this->assets->getAssetById($assetId);
        abort_unless($asset !== null, 404, 'Asset not found.');
        Gate::authorize('replaceFile', $asset);

        $asset->uploadSource = $file;
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
