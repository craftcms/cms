<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Claim;
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
use CraftCms\Cms\Support\Html;
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
use LogicException;
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
        ?CraftAssetTransformDriver $driver = null,
    ): string {
        $driver ??= $this->currentDriver($asset->getVolume());
        $mimeType = $asset->getMimeType();
        $generalConfig = Cms::config();

        if ($mimeType === 'image/gif' && ! $generalConfig->images->transformGifs) {
            throw new NotSupportedException('GIF files shouldn’t be transformed.');
        }

        if ($mimeType === 'image/svg+xml' && ! $generalConfig->images->transformSvgs) {
            throw new NotSupportedException('SVG files shouldn’t be transformed.');
        }

        $index = $this->getTransformIndex($asset, $imageTransform);
        $disk = $this->transformDisk($asset, $driver);
        $transformHasUrls = $this->transformHasUrls($asset->getVolume(), $driver);
        $uri = $this->getTransformFolderPath($asset->getVolume(), $asset->folderPath).$this->getTransformUri($asset, $index);

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
                ))->onQueue(Cms::config()->queue->lowPriorityName);

                // Prevent the page from being cached
                if (! app()->runningInConsole()) {
                    ResponseHeaders::noCache();
                }

                // Return the temporary transform URL
                return $transformHasUrls
                    ? Url::actionUrl('assets/generate-transform', ['transformId' => $index->id])
                    : $this->privateTransformUrl($index);
            }

            // Claim the transform, or wait while another request generates it.
            // If that request fails or dies, its claim ends and this request generates the transform.
            for ($try = 1; ! $index->fileExists && ! $this->claimTransformIndex($index); $try++) {
                if ($try > 60) {
                    throw new ImageTransformException(t('Failed to generate transform with id of {id}.', [
                        'id' => $index->id,
                    ]));
                }

                // Wait a second and check again
                maxPowerCaptain();
                Sleep::sleep(1);
                $index = $this->getTransformIndexModelById($index->id);
            }

            if (! $index->fileExists) {
                // Generate the transform
                try {
                    $this->generateTransform($index, $asset, $driver);
                } catch (Exception $e) {
                    $index->markFailed();
                    $this->storeTransformIndexData($index);

                    throw new ImageTransformException(t('Failed to generate transform with id of {id}.', [
                        'id' => $index->id,
                    ]), previous: $e);
                }

                $index->markGenerated();
                $this->storeTransformIndexData($index);
            }
        }

        // A private transform’s token already differs on every request, so only stored files’ URLs are revved
        if (! $transformHasUrls) {
            return $this->privateTransformUrl($index);
        }

        // The index is recreated whenever its image changes, so the date it was created revs the URL
        return AssetsHelper::revUrl($disk->url($uri), $asset, $index->dateIndexed);
    }

    public function getTransformUrlForIndex(Asset $asset, ImageTransformIndex $index, bool $immediately): string
    {
        return $this->getTransformUrl($asset, $index->getTransform(), $immediately);
    }

    public function transformHasUrlsForIndex(Asset $asset, ImageTransformIndex $index): bool
    {
        return $this->transformHasUrls($asset->getVolume(), $this->currentDriver($asset->getVolume()));
    }

    public function getTransformResponse(Asset $asset, ImageTransformIndex $index): StreamedResponse
    {
        $driver = $this->currentDriver($asset->getVolume());
        $this->getTransformUrl($asset, $index->getTransform(), true, $driver);
        $path = $this->getTransformFolderPath($asset->getVolume(), $asset->folderPath).$this->getTransformSubpath($asset, $index);
        $stream = $this->transformDisk($asset, $driver)->readStream($path);

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
        ?CraftAssetTransformDriver $driver = null,
    ): void {
        $driver ??= $this->currentDriver($asset->getVolume());
        $folderPath = $this->getTransformFolderPath($asset->getVolume(), $asset->folderPath);
        // Disk paths use forward slashes on every OS
        $diskPath = $folderPath.$this->getTransformUri($asset, $transformIndex);

        try {
            // Only a generated transform was served from its own URL. It’s built as getTransformUrl() served it,
            // revved with when the transform was generated.
            $url = $transformIndex->fileExists && $this->transformHasUrls($asset->getVolume(), $driver)
                ? Html::encodeSpaces(AssetsHelper::revUrl(
                    $this->transformDisk($asset, $driver)->url($diskPath),
                    $asset,
                    $transformIndex->dateIndexed,
                ))
                : null;

            event(new DeletingTransformedImage(
                asset: $asset,
                imageTransformIndex: $transformIndex,
                path: $diskPath,
                disk: $this->outputDisk($asset->getVolume(), $driver),
                url: $url,
            ));
            $this->transformDisk($asset, $driver)->delete($diskPath);
        } catch (Throwable) {
            $this->reportCleanupFailure($asset);
        }
    }

    /**
     * @param  ImageTransform[]  $transforms
     * @param  Asset[]  $assets
     */
    public function eagerLoadTransforms(array $transforms, array $assets): void
    {
        // Index the assets by ID
        $assetsById = Arr::keyBy($assets, 'id');

        if ($assetsById === []) {
            return;
        }

        $transformsByFingerprint = [];

        // Query for the indexes
        $results = $this->createTransformIndexQuery()
            ->whereIn('assetId', array_keys($assetsById))
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
                $indexFingerprint = $result->assetId.':'.$transformFingerprint;
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
        CraftAssetTransformDriver $driver,
    ): void {
        if (! ImageHelper::canManipulateAsImage($asset->getExtension())) {
            return;
        }

        $transformDisk = $this->transformDisk($asset, $driver);
        $transformPath = $this->getTransformFolderPath($asset->getVolume(), $asset->folderPath).$this->getTransformSubpath($asset, $index);

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
        ?CraftAssetTransformDriver $driver = null,
    ): void {
        $asset ??= app(Assets::class)->getAssetById($index->assetId);

        if (! $asset) {
            throw new ImageTransformException('Asset not found - '.$index->assetId);
        }

        $driver ??= $this->currentDriver($asset->getVolume());
        $index->detectedFormat = $index->format ?: ImageTransformHelper::detectTransformFormat($asset);
        $transformFilename = pathinfo($asset->getFilename(), PATHINFO_FILENAME).'.'.$index->detectedFormat;
        $index->filename = $transformFilename;

        $matchFound = $this->getSimilarTransformIndex($asset, $index);
        $disk = $this->transformDisk($asset, $driver);

        $target = $this->getTransformFolderPath($asset->getVolume(), $asset->folderPath).$this->getTransformSubpath($asset, $index);

        // If we have a match, copy the file.
        if ($matchFound) {
            $from = $this->getTransformFolderPath($asset->getVolume(), $asset->folderPath).$this->getTransformSubpath($asset, $matchFound);

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
            $this->generateTransformedImage($asset, $index, $driver);
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
    ): ImageTransformIndex {
        $transform = ImageTransformHelper::normalizeTransform($transform);

        if ($transform === null) {
            throw new ImageTransformException('There was a problem finding the transform.');
        }

        $transformString = ImageTransformHelper::getTransformString($transform);

        // Was it eager-loaded?
        $fingerprint = $asset->id.':'.$transformString.($transform->format === null ? '' : ':'.$transform->format);

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
        $result = $this->createTransformIndexQuery()
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
        return self::inProgressClaim()->whereClaimable($this->createTransformIndexQuery())
            ->where([
                'fileExists' => false,
                'error' => false,
            ])
            ->pluck('id')
            ->all();
    }

    /**
     * Transforms that have been in progress for more than 30 seconds count as abandoned.
     */
    public static function inProgressClaim(): Claim
    {
        return new Claim('inProgress', true, false, ttl: 30);
    }

    /**
     * Marks a transform as in progress, unless another request is already generating it.
     */
    private function claimTransformIndex(ImageTransformIndex $index): bool
    {
        $query = $this->createTransformIndexQuery()
            ->where('id', $index->id)
            ->where('fileExists', false);

        if (! self::inProgressClaim()->claim($query)) {
            return false;
        }

        $index->markGenerating();

        return true;
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

    /**
     * Returns the directory on the transform disk that holds the transforms of a volume folder’s assets.
     */
    public function getTransformFolderPath(Volume $volume, ?string $folderPath): string
    {
        return '_'.$volume->uid.'/'.($folderPath ?? '');
    }

    /**
     * Returns the URL of the directory that holds the transforms of a volume folder’s assets, or null if transforms
     * don’t have public URLs.
     */
    public function getTransformFolderUrl(Volume $volume, ?string $folderPath): ?string
    {
        if (! $this->transformHasUrls($volume, $this->currentDriver($volume))) {
            return null;
        }

        // Built like getTransformUrl() builds transform URLs, so it’s a prefix of them
        return Html::encodeSpaces($this->getTransformDisk($volume)->url($this->getTransformFolderPath($volume, $folderPath)));
    }

    /**
     * Returns the disk that a volume’s transforms are stored on.
     */
    public function getTransformDisk(Volume $volume): FilesystemAdapter
    {
        return Storage::disk($this->outputDisk($volume, $this->currentDriver($volume)));
    }

    private function transformDisk(Asset $asset, CraftAssetTransformDriver $driver): FilesystemAdapter
    {
        return Storage::disk($this->outputDisk($asset->getVolume(), $driver));
    }

    /**
     * Returns whether transforms get direct URLs on their output disk: when the source volume has public URLs, unless a
     * disk’s deprecated `asset_transformer` key sets `has_urls`. Other transforms are served through Craft’s action URL.
     */
    private function transformHasUrls(Volume $volume, CraftAssetTransformDriver $driver): bool
    {
        // Temporary uploads ignore the output disk, so they ignore its has_urls too
        if ($driver->hasUrls !== null && ! $volume->isTemporary()) {
            return $driver->hasUrls;
        }

        return $volume->sourceHasUrls();
    }

    /**
     * Returns the name of the disk the `craft` driver stores transforms on: the driver instance’s output disk, or the
     * volume’s own disk. Temporary uploads always use their own disk, since they have no setting to opt out of a public
     * output disk.
     */
    private function outputDisk(Volume $volume, CraftAssetTransformDriver $driver): string
    {
        $disk = $volume->isTemporary() ? null : $driver->outputDisk;
        $diskName = $disk === null
            ? $volume->getResolvedDisk()
            : $volume->resolveStorageTargetKey($disk, parse: false);

        if ($diskName === null) {
            throw new FilesystemException('The configured asset transform output disk does not exist.');
        }

        return $diskName;
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
     * Returns the `craft` driver instance for a volume: its transformer’s, when that uses the `craft` driver, or the
     * `craft` transformer’s, for when another driver hands a file to `craft` or a caller uses this class directly.
     */
    private function currentDriver(Volume $volume): CraftAssetTransformDriver
    {
        $transformers = app(AssetTransformers::class);
        $driver = $transformers->transformer($transformers->forVolume($volume)->name);
        $driver = $driver instanceof CraftAssetTransformDriver ? $driver : $transformers->transformer('craft');

        if (! $driver instanceof CraftAssetTransformDriver) {
            throw new LogicException('The craft asset transform driver is invalid.');
        }

        return $driver;
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

    private function getSimilarTransformIndex(Asset $asset, ImageTransformIndex $index): ?ImageTransformIndex
    {
        $transform = $index->getTransform();

        if ($asset->getExtension() !== $index->detectedFormat || $asset->getHasFocalPoint()) {
            return null;
        }

        $possibleLocations = [ImageTransformHelper::getTransformString($transform, true)];

        if ($transform->getIsNamedTransform()) {
            $possibleLocations[] = ImageTransformHelper::getTransformString($transform);
        }

        $result = $this->createTransformIndexQuery()
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

    private function createTransformIndexQuery(): Builder
    {
        return DB::table(Table::IMAGETRANSFORMINDEX)
            ->select([
                'id',
                'assetId',
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
