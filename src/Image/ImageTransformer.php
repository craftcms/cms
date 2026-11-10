<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Data\ImageTransformIndex;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\Events\DeletingTransformedImage;
use CraftCms\Cms\Image\Events\ImageTransforming;
use CraftCms\Cms\Image\Jobs\GenerateImageTransform;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\ResponseHeaders;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

use function CraftCms\Cms\maxPowerCaptain;
use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ImageTransformer
{
    /** @var array<string, array<string, mixed>> */
    private array $eagerLoadedTransformIndexes = [];

    private ?Raster $editingImage = null;

    private ?string $editingTempPath = null;

    public function getTransformUrl(
        Asset $asset,
        ImageTransform $imageTransform,
        bool $immediately,
        ?AssetTransformer $transformer = null,
    ): string {
        $transformer ??= $this->currentTransformer($asset);
        $mimeType = $asset->getMimeType();
        $generalConfig = Cms::config();

        if ($mimeType === 'image/gif' && ! $generalConfig->transformGifs) {
            throw new NotSupportedException('GIF files shouldn’t be transformed.');
        }

        if ($mimeType === 'image/svg+xml' && ! $generalConfig->transformSvgs) {
            throw new NotSupportedException('SVG files shouldn’t be transformed.');
        }

        $index = $this->getTransformIndex($asset, $imageTransform, $transformer);
        $disk = $this->transformDisk($asset, $transformer);
        $transformHasUrls = $this->transformHasUrls($asset, $transformer);
        $uri = str_replace('\\', '/', $this->getTransformBasePath($asset)).$this->getTransformUri($asset, $index);

        // If it's a local filesystem, make sure `fileExists` is accurate
        if ($disk instanceof LocalFilesystemAdapter) {
            $fileExists = $disk->exists($uri);
            $staleAfter = $imageTransform->parameterChangeTime?->getTimestamp();
            $dateModified = Arr::get($asset, 'dateModified');

            if (is_string($dateModified) || is_numeric($dateModified)) {
                $dateModified = DateTimeHelper::toDateTime($dateModified);
            }

            if ($dateModified instanceof DateTimeInterface) {
                $staleAfter = max($staleAfter ?? 0, $dateModified->getTimestamp());
            }

            // if the file exists on disk, make sure it's not stale
            if (
                $fileExists &&
                ! $index->fileExists &&
                $staleAfter !== null &&
                $disk->lastModified($uri) <= $staleAfter
            ) {
                $fileExists = false;
            }

            if ($fileExists !== $index->fileExists) {
                // Flip it and save it
                $index->fileExists = ! $index->fileExists;
                $this->storeTransformIndexData($index);
            }
        }

        if (! $index->fileExists) {
            if (! $immediately) {
                // Add a Generate Image Transform job to the queue, in case the temp URL never gets requested
                dispatch(new GenerateImageTransform(
                    transformId: $index->id,
                    description: I18N::prep('Generating image transform for {file}', [
                        'file' => $asset->getFilename(),
                    ]),
                ))->onQueue(Cms::config()->lowPriorityQueueName);

                // Prevent the page from being cached
                if (! app()->runningInConsole()) {
                    ResponseHeaders::noCache();
                }

                // Return the temporary transform URL
                return $transformHasUrls
                    ? Url::actionUrl('assets/generate-transform', ['transformId' => $index->id])
                    : $this->privateTransformUrl($index);
            }

            // Is the transform being generated by another request?
            if ($index->inProgress) {
                for ($try = 1; $try <= 30; $try++) {
                    if ($index->error) {
                        throw new ImageTransformException(t('Failed to generate transform with id of {id}.', [
                            'id' => $index->id,
                        ]));
                    }

                    // Wait a second and check again
                    maxPowerCaptain();
                    Sleep::sleep(1);
                    $index = $this->getTransformIndexModelById($index->id);
                    if (! $index->inProgress) {
                        break;
                    }
                }
            }

            // No file, then
            if (! $index->fileExists) {
                // Mark the transform as in progress
                $index->inProgress = true;
                $this->storeTransformIndexData($index);

                // Generate the transform
                try {
                    $this->generateTransform($index, $asset, $transformer);
                } catch (Exception $e) {
                    $index->inProgress = false;
                    $index->fileExists = false;
                    $index->error = true;
                    $this->storeTransformIndexData($index);

                    throw new ImageTransformException(t('Failed to generate transform with id of {id}.', [
                        'id' => $index->id,
                    ]), previous: $e);
                }

                $index->inProgress = false;
                $index->fileExists = true;
                $this->storeTransformIndexData($index);
            }
        }

        $url = $transformHasUrls
            ? $disk->url($uri)
            : $this->privateTransformUrl($index);

        $dateUpdated = Arr::get($asset, 'dateUpdated');
        if (is_string($dateUpdated) || is_numeric($dateUpdated)) {
            $dateUpdated = DateTimeHelper::toDateTime($dateUpdated);
        }

        if (! $dateUpdated instanceof DateTimeInterface || ($index->dateUpdated && $index->dateUpdated > $dateUpdated)) {
            $dateUpdated = $index->dateUpdated;
        }

        return AssetsHelper::revUrl($url, $asset, $dateUpdated);
    }

    /**
     * Returns the URL for an index row’s transform. A stale row, whose output location is no longer configured, is
     * re-targeted to the asset’s current transformer, so a cached temporary URL still resolves.
     */
    public function getTransformUrlForIndex(Asset $asset, ImageTransformIndex $index, bool $immediately): string
    {
        return $this->getTransformUrl($asset, $index->getTransform(), $immediately, $this->transformerForIndex($asset, $index));
    }

    public function transformHasUrlsForIndex(Asset $asset, ImageTransformIndex $index): bool
    {
        return $this->transformHasUrls($asset, $this->transformerForIndex($asset, $index));
    }

    public function getTransformResponse(Asset $asset, ImageTransformIndex $index): StreamedResponse
    {
        $transformer = $this->transformerForIndex($asset, $index);
        $this->getTransformUrl($asset, $index->getTransform(), true, $transformer);
        $path = $this->getTransformBasePath($asset).$this->getTransformSubpath($asset, $index);
        $stream = $this->transformDisk($asset, $transformer)->readStream($path);

        if (! is_resource($stream)) {
            throw new ImageTransformException('Unable to read generated transform.');
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, headers: [
            'Content-Disposition' => "inline; filename=\"{$index->filename}\"",
            'Content-Type' => File::getMimeTypeByExtension($index->filename ?? $asset->getFilename()) ?? 'application/octet-stream',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    public function invalidateAssetTransforms(Asset $asset): void
    {
        $transformIndexes = $this->getAllCreatedTransformsForAsset($asset);
        $this->deleteTransformIndexDataByAssetId($asset->id);

        foreach ($transformIndexes as $transformIndex) {
            $this->deleteImageTransformFile($asset, $transformIndex);
        }
    }

    public function handleAssetTransformsInvalidating(AssetTransformsInvalidating $event): void
    {
        try {
            $this->invalidateAssetTransforms($event->asset);
        } catch (Throwable) {
            $this->reportCleanupFailure($event->asset);
        }
    }

    public function deleteImageTransformFile(
        Asset $asset,
        ImageTransformIndex $transformIndex,
        ?AssetTransformer $transformer = null,
    ): void {
        $transformer ??= $this->matchingTransformerForIndex($asset, $transformIndex);

        if ($transformer === null) {
            // A stale row: its output location is no longer configured, so its file can’t be located. Leaving files
            // behind after an output location changes is expected, so this isn’t reported.
            return;
        }

        $diskPath = $this->getTransformBasePath($asset).$this->getTransformSubpath($asset, $transformIndex);

        try {
            $subPath = $this->outputSettings($transformer->settings)[1];
            $path = ($subPath ? $subPath.DIRECTORY_SEPARATOR : '').$diskPath;

            event(new DeletingTransformedImage(
                asset: $asset,
                imageTransformIndex: $transformIndex,
                path: $path,
            ));
            $this->transformDisk($asset, $transformer)->delete($diskPath);
        } catch (Throwable) {
            $this->reportCleanupFailure($asset);
        }
    }

    /**
     * @param  ImageTransform[]  $transforms
     * @param  Asset[]  $assets
     */
    public function eagerLoadTransforms(
        array $transforms,
        array $assets,
        ?AssetTransformer $transformer = null,
    ): void {
        // Index the assets by ID, along with the output location each one's transforms are indexed under
        $assetsById = Arr::keyBy($assets, 'id');
        $outputKeysByAssetId = array_map(
            fn (Asset $asset): string => $this->outputKey($asset, $transformer ?? $this->currentTransformer($asset)),
            $assetsById,
        );

        if ($outputKeysByAssetId === []) {
            return;
        }

        $transformsByFingerprint = [];

        // Query for the indexes
        $results = $this->createTransformIndexQuery()
            ->whereIn('outputKey', array_values(array_unique($outputKeysByAssetId)))
            ->whereIn('assetId', array_keys($outputKeysByAssetId))
            ->where(function (Builder $query) use ($transforms, &$transformsByFingerprint) {
                foreach ($transforms as $transform) {
                    $transformString = ImageTransformHelper::getTransformString($transform);
                    $fingerprint = ($transform->format !== null
                        ? $transformString.':'.$transform->format
                        : $transformString);

                    $transformsByFingerprint[$fingerprint] = $transform;

                    $query->orWhere(function (Builder $query) use ($transform, $transformString) {
                        $query->where('transformString', $transformString)
                            ->when(
                                $transform->format !== null,
                                fn (Builder $query) => $query->where('format', $transform->format),
                                fn (Builder $query) => $query->whereNull('format'),
                            );
                    });
                }
            })
            ->get();

        // Index the valid transform indexes by fingerprint, and capture the IDs of indexes that should be deleted
        $invalidIndexIds = [];

        foreach ($results as $result) {
            if ($result->outputKey !== ($outputKeysByAssetId[$result->assetId] ?? null)) {
                continue;
            }

            // Get the transform's fingerprint
            $transformFingerprint = $result->transformString;

            if ($result->format) {
                $transformFingerprint .= ':'.$result->format;
            }

            // Is it still valid?
            $transform = $transformsByFingerprint[$transformFingerprint];
            $asset = $assetsById[$result->assetId];
            $index = new ImageTransformIndex((array) $result);

            if ($this->validateTransformIndexResult($index, $transform, $asset)) {
                $indexFingerprint = $result->outputKey.':'.$result->assetId.':'.$transformFingerprint;
                $this->eagerLoadedTransformIndexes[$indexFingerprint] = [
                    ...(array) $result,
                    'dateIndexed' => $index->dateIndexed,
                    'dateCreated' => $index->dateCreated,
                    'dateUpdated' => $index->dateUpdated,
                ];
            } else {
                $invalidIndexIds[] = $result->id;
            }
        }

        // Delete any invalid indexes
        if (! empty($invalidIndexIds)) {
            DB::table(Table::IMAGETRANSFORMINDEX)
                ->whereIn('id', $invalidIndexIds)
                ->delete();
        }
    }

    private function getTransformSubfolder(Asset $asset, ImageTransformIndex $transformIndex): string
    {
        $path = $transformIndex->transformString;

        if (! empty($transformIndex->filename) && $transformIndex->filename !== $asset->getFilename()) {
            $path .= DIRECTORY_SEPARATOR.$asset->id;
        }

        return $path;
    }

    private function getTransformFilename(Asset $asset, ImageTransformIndex $transformIndex): string
    {
        return $transformIndex->filename ?: $asset->getFilename();
    }

    /**
     * Returns the path to a transform, relative to the asset's folder.
     */
    private function getTransformSubpath(Asset $asset, ImageTransformIndex $transformIndex): string
    {
        return $this->getTransformSubfolder($asset, $transformIndex).DIRECTORY_SEPARATOR.$this->getTransformFilename($asset, $transformIndex);
    }

    /**
     * Returns the URI for a transform, relative to the asset's folder.
     */
    private function getTransformUri(Asset $asset, ImageTransformIndex $index): string
    {
        $uri = $this->getTransformSubpath($asset, $index);

        return str_replace('\\', '/', $uri);
    }

    private function generateTransformedImage(
        Asset $asset,
        ImageTransformIndex $index,
        AssetTransformer $transformer,
    ): void {
        if (! ImageHelper::canManipulateAsImage($asset->getExtension())) {
            return;
        }

        $transformDisk = $this->transformDisk($asset, $transformer);
        $transformPath = $this->getTransformBasePath($asset).$this->getTransformSubpath($asset, $index);

        if ($transformDisk->exists($transformPath)) {
            $dateModified = $transformDisk->lastModified($transformPath);
            $parameterChangeTime = $index->getTransform()->parameterChangeTime;

            if (! $parameterChangeTime || $parameterChangeTime->getTimestamp() <= $dateModified) {
                // The file already exists and isn't stale yet
                return;
            }

            try {
                $transformDisk->delete($transformPath);
            } catch (Throwable) {
                // Unlikely, but if it got deleted while we were comparing timestamps, don't freak out.
            }
        }

        $tempPath = ImageTransformHelper::generateTransform($asset, $index->getTransform(), function () use ($index) {
            $this->storeTransformIndexData($index);
        }, $image);

        event($event = new ImageTransforming(
            asset: $asset,
            imageTransformIndex: $index,
            transform: $index->getTransform(),
            path: $transformPath,
            image: $image,
            tempPath: $tempPath,
        ));

        if ($event->tempPath !== null) {
            $tempPath = $event->tempPath;
        }

        $stream = fopen($tempPath, 'rb');

        try {
            if (! is_resource($stream) || ! $transformDisk->writeStream($transformPath, $stream)) {
                throw new FilesystemException("Unable to write stream to path: $transformPath");
            }
        } catch (Throwable $e) {
            report($e);
        }

        // when Google Cloud Storage is done with the $stream, it's no longer recognised as a valid resource
        // it comes back with type=Unknown and then causes fclose to trigger an error:
        // TypeError: fclose(): supplied resource is not a valid stream resource
        // https://github.com/craftcms/cms/issues/12878
        if (is_resource($stream)) {
            fclose($stream);
        }

        File::delete($tempPath);
    }

    /**
     * Generates a transform for the given index.
     *
     * @throws ImageTransformException
     */
    private function generateTransform(
        ImageTransformIndex $index,
        ?Asset $asset = null,
        ?AssetTransformer $transformer = null,
    ): void {
        $asset ??= app(Assets::class)->getAssetById($index->assetId);

        if (! $asset) {
            throw new ImageTransformException('Asset not found - '.$index->assetId);
        }

        $transformer ??= $this->transformerForIndex($asset, $index);
        $index->detectedFormat = $index->format ?: ImageTransformHelper::detectTransformFormat($asset);
        $transformFilename = pathinfo($asset->getFilename(), PATHINFO_FILENAME).'.'.$index->detectedFormat;
        $index->filename = $transformFilename;

        $matchFound = $this->getSimilarTransformIndex($asset, $index, $transformer);
        $disk = $this->transformDisk($asset, $transformer);

        $target = $this->getTransformBasePath($asset).$this->getTransformSubpath($asset, $index);

        // If we have a match, copy the file.
        if ($matchFound) {
            $from = $this->getTransformBasePath($asset).$this->getTransformSubpath($asset, $matchFound);

            // Sanity check
            try {
                if ($disk->exists($target)) {
                    return;
                }

                if (! $disk->copy($from, $target)) {
                    throw new FilesystemException("Unable to copy $from to $target");
                }
            } catch (Throwable $exception) {
                throw new ImageTransformException('There was a problem re-using an existing transform.', 0, $exception);
            }
        } else {
            $this->generateTransformedImage($asset, $index, $transformer);
        }

        if (! $disk->exists($target)) {
            throw new ImageTransformException('There was a problem generating the image transform.');
        }
    }

    /**
     * Gets a transform index row. If it doesn't exist, creates one.
     *
     * @param  ImageTransform|string|array<string,mixed>|null  $transform
     *
     * @throws ImageTransformException if the transform cannot be found by the handle
     */
    public function getTransformIndex(
        Asset $asset,
        mixed $transform,
        ?AssetTransformer $transformer = null,
    ): ImageTransformIndex {
        $outputKey = $this->outputKey($asset, $transformer ?? $this->currentTransformer($asset));
        $transform = ImageTransformHelper::normalizeTransform($transform);

        if ($transform === null) {
            throw new ImageTransformException('There was a problem finding the transform.');
        }

        $transformString = ImageTransformHelper::getTransformString($transform);

        // Was it eager-loaded?
        $fingerprint = $outputKey.':'.$asset->id.':'.$transformString.($transform->format === null ? '' : ':'.$transform->format);

        if (isset($this->eagerLoadedTransformIndexes[$fingerprint])) {
            $result = $this->eagerLoadedTransformIndexes[$fingerprint];
            $timeZone = null;

            foreach (['dateIndexed', 'dateCreated', 'dateUpdated'] as $attribute) {
                if ($result[$attribute] instanceof DateTimeInterface) {
                    $timeZone ??= Cms::timezone();
                    $result[$attribute] = Date::instance($result[$attribute])->setTimezone($timeZone);
                }
            }

            return new ImageTransformIndex($result);
        }

        // Check if an entry exists already
        $result = $this->createTransformIndexQuery($outputKey)
            ->where('assetId', $asset->id)
            ->where('transformString', $transformString)
            ->when(
                $transform->format,
                fn (Builder $query) => $query->where('format', $transform->format),
                fn (Builder $query) => $query->whereNull('format'),
            )
            ->first();

        if ($result) {
            $result = (array) $result;

            $existingIndex = new ImageTransformIndex($result);

            if ($this->validateTransformIndexResult($existingIndex, $transform, $asset)) {
                return $existingIndex;
            }

            // Delete the out-of-date record
            DB::table(Table::IMAGETRANSFORMINDEX)->delete($result['id']);

            // And the generated transform itself, too
            $this->deleteImageTransformFile($asset, $existingIndex);
        }

        $detectedFormat = $transform->format ?: ImageTransformHelper::detectTransformFormat($asset);
        $transformFilename = pathinfo($asset->getFilename(), PATHINFO_FILENAME).'.'.$detectedFormat;

        $index = new ImageTransformIndex([
            'assetId' => $asset->id,
            'format' => $transform->format,
            'outputKey' => $outputKey,
            'dateIndexed' => now(),
            'transformString' => $transformString,
            'fileExists' => false,
            'inProgress' => false,
            'filename' => $transformFilename,
        ]);
        $index->setTransform($transform);

        $this->storeTransformIndexData($index);

        return $index;
    }

    private function validateTransformIndexResult(ImageTransformIndex $result, ImageTransform $transform, Asset $asset): bool
    {
        if ($result->dateIndexed === null) {
            return true;
        }

        if (
            $asset->dateModified !== null &&
            $result->dateIndexed->getTimestamp() < $asset->dateModified->getTimestamp()
        ) {
            return false;
        }

        if (! $transform->getIsNamedTransform()) {
            return true;
        }

        return $transform->parameterChangeTime === null ||
            $result->dateIndexed->getTimestamp() >= $transform->parameterChangeTime->getTimestamp();
    }

    public function storeTransformIndexData(ImageTransformIndex $index): void
    {
        $values = Query::prepareValuesForDb(
            $index->toArray([
                'assetId',
                'outputKey',
                'filename',
                'format',
                'transformString',
                'volumeId',
                'fileExists',
                'inProgress',
                'error',
                'dateIndexed',
            ], [], false)
        );
        $now = Query::prepareDateForDb(now());

        if ($index->id !== null) {
            $this->createTransformIndexQuery()
                ->where('id', $index->id)
                ->update([
                    'dateUpdated' => $now,
                    ...$values,
                ]);
        } else {
            $index->id = DB::table(Table::IMAGETRANSFORMINDEX)
                ->insertGetId([
                    'dateCreated' => $now,
                    'dateUpdated' => $now,
                    'uid' => Str::uuid(),
                    ...$values,
                ]);
        }
    }

    /**
     * @return int[]
     */
    public function getPendingTransformIndexIds(): array
    {
        return $this->createTransformIndexQuery()
            ->where([
                'fileExists' => false,
                'inProgress' => false,
                'error' => false,
            ])
            ->pluck('id')
            ->all();
    }

    public function getTransformIndexModelById(int $transformId): ?ImageTransformIndex
    {
        $result = $this->createTransformIndexQuery()
            ->where('id', $transformId)
            ->first();

        return $result ? new ImageTransformIndex((array) $result) : null;
    }

    public function startImageEditing(Asset $asset): void
    {
        $imageCopy = $asset->getCopyOfFile();

        if (File::isSvg($imageCopy)) {
            $size = max($asset->width, $asset->height) ?? 1000;
            /** @var Raster $image */
            $image = app(Images::class)->loadImage($imageCopy, true, $size);
        } else {
            /** @var Raster $image */
            $image = app(Images::class)->loadImage($imageCopy);
        }

        // TODO Is this hacky? It seems hacky.
        // We're rasterizing SVG, we have to make sure that the filename change does not get lost
        if (strtolower($asset->getExtension()) === 'svg') {
            unlink($imageCopy);
            $imageCopy = preg_replace('/(svg)$/i', 'png', $imageCopy);
            $asset->setFilename(preg_replace('/(svg)$/i', 'png', $asset->getFilename()));
        }

        $this->editingImage = $image;
        $this->editingTempPath = $imageCopy;
    }

    public function flipImage(bool $flipX, bool $flipY): void
    {
        if ($flipX) {
            $this->editingImage->flipHorizontally();
        }

        if ($flipY) {
            $this->editingImage->flipVertically();
        }
    }

    public function scaleImage(int $width, int $height): void
    {
        $this->editingImage->scaleToFit($width, $height);
    }

    public function rotateImage(float $degrees): void
    {
        $this->editingImage->rotate($degrees);
    }

    public function getEditedImageWidth(): int
    {
        return $this->editingImage->getWidth();
    }

    public function getEditedImageHeight(): int
    {
        return $this->editingImage->getHeight();
    }

    public function crop(int $x, int $y, int $width, int $height): void
    {
        $this->editingImage->crop($x, $x + $width, $y, $y + $height);
    }

    public function finishImageEditing(): string
    {
        $this->editingImage->saveAs($this->editingTempPath);

        $tempPath = $this->editingTempPath;
        $this->editingImage = null;
        $this->editingTempPath = null;

        return $tempPath;
    }

    public function cancelImageEditing(): string
    {
        $tempPath = $this->editingTempPath;
        $this->editingImage = null;
        $this->editingTempPath = null;

        return $tempPath;
    }

    private function getTransformBasePath(Asset $asset): string
    {
        return $asset->getVolume()->uid.DIRECTORY_SEPARATOR.($asset->folderPath ?? '');
    }

    private function transformDisk(Asset $asset, AssetTransformer $transformer): FilesystemAdapter
    {
        [$diskReference, $subpath] = $this->outputSettings($transformer->settings);

        if ($diskReference === null) {
            return $asset->getVolume()->sourceDisk();
        }

        $diskName = $asset->getVolume()->resolveStorageTargetKey($diskReference);
        if ($diskName === null) {
            throw new FilesystemException('The configured asset transform output disk does not exist.');
        }

        if ($subpath === null) {
            return Storage::disk($diskName);
        }

        $disk = Storage::build(['driver' => 'scoped', 'disk' => $diskName, 'prefix' => $subpath]);
        if (! $disk instanceof FilesystemAdapter) {
            throw new FilesystemException('Unable to create the scoped asset transform output disk.');
        }

        return $disk;
    }

    private function transformHasUrls(Asset $asset, AssetTransformer $transformer): bool
    {
        [$diskReference, , $hasUrls] = $this->outputSettings($transformer->settings);

        return $diskReference === null ? $asset->getVolume()->sourceHasUrls() : $hasUrls;
    }

    /**
     * Returns the `craft` driver’s output disk, output subpath, and whether the output disk has public URLs. The subpath
     * and URL setting only apply with an output disk.
     *
     * @param  array<string,mixed>  $settings
     * @return array{string|null,string|null,bool}
     */
    private function outputSettings(array $settings): array
    {
        $disk = $settings['output_disk'] ?? null;
        $subpath = $settings['subpath'] ?? '';

        if ($disk !== null && ! is_string($disk)) {
            throw new FilesystemException('The configured asset transform output disk is invalid.');
        }

        if (! is_string($subpath)) {
            throw new FilesystemException('The configured asset transform output subpath is invalid.');
        }

        if ($disk === null || $disk === '') {
            return [null, null, false];
        }

        $subpath = trim(str_replace('\\', '/', $subpath), '/');
        $subpath = $subpath === '' ? null : $subpath;

        return [$disk, $subpath, ($settings['has_urls'] ?? false) === true];
    }

    private function deleteTransformIndexDataByAssetId(int $assetId): void
    {
        $this->createTransformIndexQuery()
            ->where('assetId', $assetId)
            ->delete();
    }

    private function reportCleanupFailure(Asset $asset): void
    {
        report(new RuntimeException("Unable to clean asset transform results for asset [{$asset->id}] with driver [craft]."));
    }

    /**
     * Returns the `craft` transformer for an asset’s disk. A disk that uses another driver gets the `craft` defaults,
     * for when that driver hands a file to `craft` or a caller uses this class directly.
     */
    private function currentTransformer(Asset $asset): AssetTransformer
    {
        $transformer = app(AssetTransformers::class)->forDisk($asset->getVolume()->getResolvedFsTarget());

        return $transformer->driver === 'craft' ? $transformer : new AssetTransformer('craft');
    }

    /**
     * Returns the index key for an asset’s transforms: its source disk plus a hash of where the transformer stores
     * files. Changing the output disk or subpath changes the key, so new transforms are generated at the new location.
     */
    private function outputKey(Asset $asset, AssetTransformer $transformer): string
    {
        [$outputDisk, $subpath] = $this->outputSettings($transformer->settings);
        $sourceDisk = $asset->getVolume()->getResolvedFsTarget() ?? '';

        // Without an output disk, transforms are stored on the volume's source disk, below the volume's subpath. That
        // must hash differently from an explicit `output_disk` naming the same disk, which doesn't use that subpath.
        $location = $outputDisk === null ? null : [$outputDisk, $subpath];

        return 'craft:'.$sourceDisk.':'.substr(hash('sha256', serialize($location)), 0, 16);
    }

    /**
     * Returns the transformer that created an index row, or `null` if the row is stale: no current `craft`
     * transformer for the asset stores files where the row says.
     */
    private function matchingTransformerForIndex(Asset $asset, ImageTransformIndex $index): ?AssetTransformer
    {
        // The `craft` defaults match rows written for another driver handing files to `craft`, or before the disk set
        // an `asset_transformer` key.
        $candidates = [$this->currentTransformer($asset), new AssetTransformer('craft')];

        foreach ($candidates as $candidate) {
            if ($this->outputKey($asset, $candidate) === $index->outputKey) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Returns the transformer that created an index row, re-targeting a stale row to the asset’s current transformer.
     */
    private function transformerForIndex(Asset $asset, ImageTransformIndex $index): AssetTransformer
    {
        return $this->matchingTransformerForIndex($asset, $index) ?? $this->currentTransformer($asset);
    }

    /**
     * Returns an array of ImageTransformIndex models for all created transforms for an asset.
     *
     * @return ImageTransformIndex[]
     */
    private function getAllCreatedTransformsForAsset(Asset $asset): array
    {
        return $this->createTransformIndexQuery()
            ->where('assetId', $asset->id)
            ->get()
            ->map(fn (object $result) => new ImageTransformIndex((array) $result))
            ->all();
    }

    private function getSimilarTransformIndex(
        Asset $asset,
        ImageTransformIndex $index,
        AssetTransformer $transformer,
    ): ?ImageTransformIndex {
        $transform = $index->getTransform();

        if ($asset->getExtension() !== $index->detectedFormat || $asset->getHasFocalPoint()) {
            return null;
        }

        $possibleLocations = [ImageTransformHelper::getTransformString($transform, true)];

        if ($transform->getIsNamedTransform()) {
            $possibleLocations[] = ImageTransformHelper::getTransformString($transform);
        }

        $result = $this->createTransformIndexQuery($this->outputKey($asset, $transformer))
            ->where([
                'assetId' => $asset->id,
                'fileExists' => true,
                'format' => $index->detectedFormat,
            ])
            ->whereIn('transformString', $possibleLocations)
            ->whereNot('id', $index->id)
            ->first();

        return $result ? new ImageTransformIndex((array) $result) : null;
    }

    private function privateTransformUrl(ImageTransformIndex $index): string
    {
        return Url::actionUrl('assets/generate-transform', [
            'transformToken' => Crypt::encryptString((string) $index->id),
        ]);
    }

    /**
     * @param  string|null  $outputKey  Limits the query to one output location. Otherwise it covers every `craft` row.
     */
    private function createTransformIndexQuery(?string $outputKey = null): Builder
    {
        $query = DB::table(Table::IMAGETRANSFORMINDEX);

        if ($outputKey !== null) {
            $query->where('outputKey', $outputKey);
        } else {
            $query->where('outputKey', 'like', 'craft:%');
        }

        return $query
            ->select([
                'id',
                'assetId',
                'outputKey',
                'filename',
                'format',
                'transformString',
                'fileExists',
                'inProgress',
                'error',
                'dateIndexed',
                'dateUpdated',
                'dateCreated',
            ]);
    }
}
