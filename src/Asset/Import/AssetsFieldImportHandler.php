<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Import;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\ImportFileConflict;
use CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException;
use CraftCms\Cms\Asset\Exceptions\AssetException;
use CraftCms\Cms\Asset\Exceptions\FileException;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Import\Data\FieldMappingSetting;
use CraftCms\Cms\Import\FieldHandlers\FieldImportHandlerInterface;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\Facades\Assets as AssetsService;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Url;
use CraftCms\UrlValidator\UrlValidator;
use InvalidArgumentException;
use Override;
use Throwable;

use function CraftCms\Cms\craftAuth;
use function CraftCms\Cms\t;

/**
 * Turns files referenced in an imported Assets field value (absolute URLs or local paths) into assets.
 *
 * @since 6.0.0
 */
class AssetsFieldImportHandler implements FieldImportHandlerInterface
{
    #[Override]
    public function normalizeValue(FieldInterface $field, mixed $value, BaseImporter $importer, ?ElementInterface $rootOwner = null, array $importSettings = []): mixed
    {
        if (! $field instanceof AssetsField) {
            return $value;
        }

        if (is_string($value) || is_int($value)) {
            $value = [$value];
        }

        if (! is_array($value) || ! array_is_list($value)) {
            return $value;
        }

        $items = [];

        foreach ($value as $item) {
            if ($item instanceof Asset) {
                $item = $item->id;
            }

            if (is_numeric($item)) {
                $items[] = (int) $item;
            } elseif (is_string($item) && trim($item) !== '') {
                $items[] = trim($item);
            }
        }

        // nothing to upload, so it’s just a list of asset IDs
        if (! array_any($items, fn ($item) => is_string($item))) {
            return $value;
        }

        $conflict = ImportFileConflict::tryFrom((string) ($importSettings['fileConflict'] ?? '')) ?? ImportFileConflict::UseExisting;

        // resolve files that are already there up front, so they don’t make the element look changed
        if ($conflict === ImportFileConflict::UseExisting && $rootOwner?->id) {
            $items = $this->resolveExistingFiles($field, $items, $rootOwner);

            if (! array_any($items, fn ($item) => is_string($item))) {
                return array_values(array_unique($items));
            }
        }

        // if the upload location can’t be resolved yet (e.g. an `{id}` subpath on a new entry), this is the user’s temp folder;
        // the field moves the new assets from there into place once the element is saved
        $isCanonical = $rootOwner?->getRootOwner()->getIsCanonical() ?? true;
        $folder = Folders::getFolderById($field->resolveDynamicPathToFolderId($rootOwner, $isCanonical)) ?? AssetsService::getUserTemporaryUploadFolder();

        return $this->importedAssetIds($field, $items, $conflict, $importer, $folder);
    }

    #[Override]
    public function mappingSettings(FieldInterface $field): array
    {
        return [
            new FieldMappingSetting(
                name: 'fileConflict',
                label: t('What should happen when an incoming file matches an existing one?'),
                options: ImportFileConflict::asOptions(),
                default: ImportFileConflict::UseExisting->value,
                instructions: t('Incoming files are matched against existing assets by filename, within the folder they’d be uploaded to.'),
            ),
        ];
    }

    /**
     * Turns imported asset IDs and file references into the list of asset IDs to relate, in their incoming order,
     * creating or reusing assets for the files.
     *
     * @param  list<int|string>  $items
     * @return list<int>
     */
    private function importedAssetIds(AssetsField $field, array $items, ImportFileConflict $conflict, BaseImporter $importer, VolumeFolder $folder): array
    {
        $urlValidator = $importer::urlValidator();

        // a temp folder means the real upload location can’t be resolved until the element is saved,
        // so there’s nothing to match against; the new assets get moved there after the save
        if ($folder->volumeId === null && $conflict !== ImportFileConflict::CreateNew) {
            // ImportLog::info("The {$field->name} field’s upload location can’t be resolved before the element is saved, so incoming files will be added as new assets.");
        }

        $assetIds = [];

        foreach ($items as $item) {
            if (is_int($item)) {
                $assetIds[] = $item;

                continue;
            }

            try {
                $assetIds[] = $this->importFile($field, $item, $conflict, $urlValidator, $importer, $folder);
            } catch (Throwable $e) {
                ImportLog::warning("Couldn’t import “{$item}” into the {$field->name} field: ".$e->getMessage());
            }
        }

        return array_values(array_unique($assetIds));
    }

    /**
     * Creates or reuses an asset for an imported file, and returns its ID.
     * Replacing an existing asset’s file is deferred until the item has been imported.
     *
     * @throws AssetException if the file isn’t allowed or the asset can’t be saved
     */
    private function importFile(AssetsField $field, string $source, ImportFileConflict $conflict, UrlValidator $urlValidator, BaseImporter $importer, VolumeFolder $folder): int
    {
        $filename = $this->filename($source);
        $tempPath = null;

        try {
            // without an extension, the file has to be fetched to find out what it is
            if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
                [$tempPath, $extension] = $this->fetchFile($source, $urlValidator, $filename);
                $filename .= ".$extension";
            }

            $this->ensureAllowed($field, $filename);

            $existingAsset = $conflict === ImportFileConflict::CreateNew ? null : $this->findExistingAsset($filename, $folder);

            if ($existingAsset !== null) {
                // only replace the file once the item has gone through
                if ($conflict === ImportFileConflict::Replace) {
                    $importer->afterItemImported(fn () => $this->replaceFile($field, $existingAsset, $source, $urlValidator));
                }

                if ($tempPath !== null) {
                    File::delete($tempPath);
                }

                return $existingAsset->id;
            }

            $tempPath ??= $this->fetchFile($source, $urlValidator, $filename)[0];
            $asset = $this->createAsset($tempPath, $filename, $folder);
        } catch (Throwable $e) {
            if ($tempPath !== null) {
                File::delete($tempPath);
            }

            throw $e;
        }

        if ($asset->id === null) {
            File::delete($tempPath);

            throw new AssetException('Couldn’t save the asset due to validation errors: '.implode(', ', $asset->getFirstErrors()));
        }

        return $asset->id;
    }

    /**
     * Replaces an existing asset’s file with an imported one, logging any failure.
     */
    private function replaceFile(AssetsField $field, Asset $asset, string $source, UrlValidator $urlValidator): void
    {
        $tempPath = null;

        try {
            [$tempPath] = $this->fetchFile($source, $urlValidator, $asset->getFilename());
            AssetsService::replaceAssetFile($asset, $tempPath, $asset->getFilename());

            if (! empty($asset->getFirstErrors())) {
                throw new AssetException('Couldn’t save the asset due to validation errors: '.implode(', ', $asset->getFirstErrors()));
            }
        } catch (Throwable $e) {
            if ($tempPath !== null) {
                File::delete($tempPath);
            }

            ImportLog::warning("Couldn’t replace the file of “{$asset->getFilename()}” with “{$source}” in the {$field->name} field: ".$e->getMessage());
        }
    }

    /**
     * Swaps the imported file references that match an existing asset in the element’s upload folder for that asset’s ID.
     *
     * @param  list<int|string>  $items
     * @return list<int|string>
     */
    private function resolveExistingFiles(AssetsField $field, array $items, ElementInterface $element): array
    {
        try {
            $folder = Folders::getFolderById($field->resolveDynamicPathToFolderId($element, false));
        } catch (Throwable) {
            return $items;
        }

        if ($folder === null || $folder->volumeId === null) {
            return $items;
        }

        return array_map(function (int|string $item) use ($folder): int|string {
            if (is_int($item)) {
                return $item;
            }

            $filename = $this->filename($item);

            if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
                return $item;
            }

            return $this->findExistingAsset($filename, $folder)->id ?? $item;
        }, $items);
    }

    /**
     * Returns the asset filename for an imported file reference.
     */
    private function filename(string $source): string
    {
        $basename = pathinfo(Url::stripQueryString($source), PATHINFO_BASENAME);

        return AssetsHelper::prepareAssetName(Url::isAbsoluteUrl($source) ? rawurldecode($basename) : $basename);
    }

    /**
     * Ensures an imported file’s extension is allowed, both site-wide and by the field.
     *
     * @throws AssetDisallowedExtensionException if it isn’t
     */
    private function ensureAllowed(AssetsField $field, string $filename): void
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowedExtensions = $field->getAllowedExtensions();

        if (
            ! in_array($extension, Cms::config()->allowedFileExtensions, true) ||
            (! empty($allowedExtensions) && ! in_array($extension, $allowedExtensions, true))
        ) {
            throw new AssetDisallowedExtensionException(t('“{filename}” is not allowed in this field.', [
                'filename' => $filename,
            ]));
        }
    }

    /**
     * Returns the existing asset in the folder with the given filename, if there is one.
     */
    private function findExistingAsset(string $filename, VolumeFolder $folder): ?Asset
    {
        if ($folder->volumeId === null) {
            return null;
        }

        return Asset::find()
            ->folderId($folder->id)
            ->filename(Query::escapeParam($filename))
            ->status(null)
            ->site('*')
            ->unique()
            ->one();
    }

    /**
     * Creates and saves a new asset in the given folder from a file at a temp path.
     */
    private function createAsset(string $tempPath, string $filename, VolumeFolder $folder): Asset
    {
        $asset = new Asset;
        $asset->tempFilePath = $tempPath;
        $asset->setFilename($filename);
        $asset->setMimeType(File::getMimeType($tempPath, checkExtension: false));
        $asset->newFolderId = $folder->id;
        $asset->setVolumeId($folder->volumeId);
        $asset->uploaderId = craftAuth()->id();
        $asset->avoidFilenameConflicts = true;
        $asset->ruleset->useScenario(AssetRules::SCENARIO_CREATE);

        Elements::saveElement($asset);

        return $asset;
    }

    /**
     * Fetches an imported file (an absolute URL or a local path) to a temp path, and returns the path along with
     * the file’s extension, taken from the content type when the filename doesn’t have one.
     *
     * @return array{0: string, 1: string}
     *
     * @throws AssetException if the file can’t be fetched, is too large, or its type can’t be determined
     */
    private function fetchFile(string $source, UrlValidator $urlValidator, string $filename): array
    {
        // validate a local path before anything gets created for it
        $localPath = Url::isAbsoluteUrl($source) ? null : AssetsHelper::resolveImportFilePath($source);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $tempPath = AssetsHelper::tempFilePath($extension ?: 'tmp');

        try {
            $mimeType = null;

            if ($localPath === null) {
                $mimeType = AssetsHelper::downloadUrl($urlValidator, $source, $tempPath)->header('Content-Type');
            } elseif (! copy($localPath, $tempPath)) {
                throw new FileException("Couldn’t copy $localPath to a temp location.");
            }

            if (filesize($tempPath) > Cms::config()->maxUploadFileSize) {
                throw new AssetException(t('“{filename}” is too large.', [
                    'filename' => $filename,
                ]));
            }

            if ($extension === '') {
                $mimeType = strtolower(trim(explode(';', $mimeType ?: (File::getMimeType($tempPath, checkExtension: false) ?? ''))[0]));

                try {
                    $extension = File::getExtensionByMimeType($mimeType);
                } catch (InvalidArgumentException $e) {
                    throw new AssetDisallowedExtensionException(t('“{filename}” is not allowed in this field.', [
                        'filename' => $filename,
                    ]), previous: $e);
                }
            }
        } catch (Throwable $e) {
            File::delete($tempPath);

            throw $e;
        }

        return [$tempPath, $extension];
    }
}
