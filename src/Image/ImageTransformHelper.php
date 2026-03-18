<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use craft\helpers\Assets;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\AssetException;
use CraftCms\Cms\Asset\Exceptions\AssetOperationException;
use CraftCms\Cms\Asset\Exceptions\ImageException;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Filesystem\Exceptions\FsObjectNotFoundException;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Rules\ColorRule;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Imagine\Image\Format;
use InvalidArgumentException;
use Symfony\Component\Finder\Finder;

use function CraftCms\Cms\t;

class ImageTransformHelper
{
    /**
     * @var string The pattern to use for matching against a transform string.
     */
    public const string TRANSFORM_STRING_PATTERN = '/_(?P<width>\d+|AUTO)x(?P<height>\d+|AUTO)_(?P<mode>[a-z]+)(?:_(?P<position>[a-z\-]+))?(?:_(?P<quality>\d+))?(?:_(?P<interlace>[a-z]+))?(?:_(?P<fill>[0-9a-f]{6}|transparent))?(?:_(?P<upscale>ns))?/i';

    public static function createTransformFromString(string $transformString): ImageTransform
    {
        if (! preg_match(self::TRANSFORM_STRING_PATTERN, $transformString, $matches)) {
            throw new ImageTransformException('Cannot create a transform from string: '.$transformString);
        }

        if (mb_strtoupper($matches['width']) === 'AUTO') {
            unset($matches['width']);
        }
        if (mb_strtoupper($matches['height']) === 'AUTO') {
            unset($matches['height']);
        }

        if (empty($matches['quality'])) {
            unset($matches['quality']);
        }

        if (! empty($matches['fill'])) {
            $fill = ColorRule::normalizeColor($matches['fill']);
        }

        return new ImageTransform([
            'width' => $matches['width'] ?? null,
            'height' => $matches['height'] ?? null,
            'mode' => $matches['mode'],
            'position' => $matches['position'] ?? 'center-center',
            'quality' => $matches['quality'] ?? null,
            'interlace' => $matches['interlace'] ?? 'none',
            'fill' => $fill ?? null,
            'upscale' => ($matches['upscale'] ?? null) !== 'ns',
            'transformer' => ImageTransform::DEFAULT_TRANSFORMER,
        ]);
    }

    /**
     * Detect the auto web-safe format for the Asset. Returns null, if the Asset is not an image.
     *
     * @throws AssetOperationException If attempting to detect an image format for a non-image.
     */
    public static function detectTransformFormat(Asset $asset): string
    {
        $ext = strtolower($asset->getExtension());

        if (ImageHelper::isWebSafe($ext)) {
            return $ext;
        }

        if ($asset->kind !== Asset::KIND_IMAGE) {
            throw new AssetOperationException(t('Tried to detect the appropriate image format for a non-image!'));
        }

        return 'jpg';
    }

    /**
     * Extend a transform by taking an existing transform and overriding its parameters.
     */
    public static function extendTransform(ImageTransform $transform, array $parameters): ImageTransform
    {
        if (! empty($parameters)) {
            // Don't change the same transform
            $transform = clone $transform;

            $attributes = $transform->attributes();

            $nullables = [
                'id',
                'name',
                'handle',
                'uid',
                'parameterChangeTime',
            ];

            foreach ($parameters as $name => $value) {
                if (in_array($name, $attributes, true)) {
                    $transform->$name = $value;
                }
            }

            foreach ($nullables as $name) {
                $transform->$name = null;
            }
        }

        return $transform;
    }

    /**
     * Get a local image source to use for transforms.
     *
     * @throws FsObjectNotFoundException If the file cannot be found.
     */
    public static function getLocalImageSource(Asset $asset): string
    {
        $volume = $asset->getVolume();
        $imageSourcePath = $asset->getImageTransformSourcePath();

        try {
            $isLocalFs = $volume->sourceDisk() instanceof LocalFilesystemAdapter;

            if (! $isLocalFs) {
                // This is a non-local fs
                if (! is_file($imageSourcePath) || filesize($imageSourcePath) === 0) {
                    if (is_file($imageSourcePath)) {
                        // Delete since it's a 0-byter
                        File::delete($imageSourcePath);
                    }

                    $prefix = pathinfo($asset->getFilename(), PATHINFO_FILENAME).'.delimiter.';
                    $extension = $asset->getExtension();
                    $tempFilename = uniqid($prefix, true).'.'.$extension;
                    $tempPath = Path::temp();
                    $tempFilePath = Path::temp($tempFilename);

                    // Fetch existing temp files for this image and clean them up.
                    $finder = Finder::create()
                        ->ignoreDotFiles(false)
                        ->ignoreVCS(false)
                        ->files()
                        ->in($tempPath)
                        ->name($prefix.'*'.'.'.$extension);

                    foreach ($finder as $file) {
                        File::delete($file->getPathname());
                    }

                    Assets::downloadFile($volume->sourceDisk(), $asset->getPath(), $tempFilePath);

                    if (! is_file($tempFilePath) || filesize($tempFilePath) === 0) {
                        if (is_file($tempFilePath) && ! File::delete($tempFilePath)) {
                            Log::warning("Unable to delete the file \"$tempFilePath\".", [__METHOD__]);
                        }
                        throw new FilesystemException(t('Tried to download the source file for image "{file}", but it was 0 bytes long.', [
                            'file' => $asset->getFilename(),
                        ]));
                    }

                    // we've downloaded the file, now store it
                    self::storeLocalSource($tempFilePath, $imageSourcePath);

                    // And delete it after the request, if nobody wants it.
                    if (Cms::config()->maxCachedCloudImageSize === 0) {
                        app()->terminating(function () use ($imageSourcePath) {
                            File::delete($imageSourcePath);
                        });
                    }

                    if (! File::delete($tempFilePath)) {
                        Log::warning("Unable to delete the file \"$tempFilePath\".", [__METHOD__]);
                    }
                }
            }
        } catch (AssetException) {
            // Make sure we throw a new exception
            $imageSourcePath = false;
        }

        if (! is_file($imageSourcePath)) {
            throw new FsObjectNotFoundException("The file \"{$asset->getFilename()}\" does not exist.");
        }

        return $imageSourcePath;
    }

    /**
     * Get the transform string for a given asset image transform.
     *
     * @param  bool  $ignoreHandle  whether the transform handle should be ignored
     */
    public static function getTransformString(ImageTransform $transform, bool $ignoreHandle = false): string
    {
        if (! $ignoreHandle && ! empty($transform->handle)) {
            return '_'.$transform->handle;
        }

        $position = preg_match('/^(top|center|bottom)-(left|center|right)$/', $transform->position)
            ? $transform->position
            : 'center-center';

        $width = ($transform->width ?: 'AUTO').'x'.($transform->height ?: 'AUTO');

        return '_'.implode('_', array_filter([
            $width,
            $transform->mode,
            $position,
            $transform->quality,
            $transform->interlace,
            $transform->fill ? ltrim($transform->fill, '#') : '',
            $transform->upscale ? '' : 'ns',
        ]));
    }

    public static function parseTransformString(string $str): array
    {
        if (! preg_match('/^_?(?P<width>\d+|AUTO)x(?P<height>\d+|AUTO)_(?P<mode>[a-z]+)_(?P<position>[a-z\-]+)(?:_(?P<quality>\d+))?_(?P<interlace>[a-z]+)(?:_(?P<fill>transparent|[0-9a-f]{3}|[0-9a-f]{6}))?(?:_(?P<upscale>ns))?$/', $str, $match)) {
            throw new InvalidArgumentException("Invalid transform string: $str");
        }

        $upscale = ($match['upscale'] ?? null) ?: null;
        $fill = ($match['fill'] ?? null) ?: null;

        if (! empty($fill) && $fill !== 'transparent') {
            $fill = '#'.$fill;
        }

        return [
            'width' => $match['width'] !== 'AUTO' ? (int) $match['width'] : null,
            'height' => $match['height'] !== 'AUTO' ? (int) $match['height'] : null,
            'mode' => $match['mode'],
            'position' => $match['position'],
            'quality' => $match['quality'] ? (int) $match['quality'] : null,
            'interlace' => $match['interlace'],
            'fill' => $fill,
            'upscale' => $upscale !== 'ns',
        ];
    }

    /**
     * Normalize a transform from handle or a set of properties to an ImageTransform.
     *
     * @throws ImageTransformException if $transform is an invalid transform handle
     */
    public static function normalizeTransform(mixed $transform): ?ImageTransform
    {
        if (! $transform) {
            return null;
        }

        if ($transform instanceof ImageTransform) {
            return $transform;
        }

        if (is_object($transform)) {
            $transform = Arr::toArray($transform);
        }

        if (is_array($transform)) {
            if (! empty($transform['width']) && ! is_numeric($transform['width'])) {
                Log::warning("Invalid transform width: {$transform['width']}", [__METHOD__]);
                $transform['width'] = null;
            }

            if (! empty($transform['height']) && ! is_numeric($transform['height'])) {
                Log::warning("Invalid transform height: {$transform['height']}", [__METHOD__]);
                $transform['height'] = null;
            }

            if (! empty($transform['fill'])) {
                $normalizedValue = ColorRule::normalizeColor($transform['fill']);
                $colorValidator = Validator::make(
                    data: ['fill' => $normalizedValue],
                    rules: ['fill' => new ColorRule],
                );

                if ($colorValidator->passes()) {
                    $transform['fill'] = $normalizedValue;
                } else {
                    Log::warning("Invalid transform fill: {$transform['fill']}", [__METHOD__]);
                    $transform['fill'] = null;
                }
            }

            if (array_key_exists('transform', $transform)) {
                $baseTransform = self::normalizeTransform(Arr::pull($transform, 'transform'));

                return self::extendTransform($baseTransform, $transform);
            }

            return new ImageTransform($transform);
        }

        if (is_string($transform)) {
            if (preg_match(self::TRANSFORM_STRING_PATTERN, $transform)) {
                return self::createTransformFromString($transform);
            }

            $transform = Str::chopStart($transform, '_');
            if (($transformModel = app(ImageTransforms::class)->getTransformByHandle($transform)) === null) {
                throw new ImageTransformException(t('Invalid transform handle: {handle}', ['handle' => $transform]));
            }

            return $transformModel;
        }

        return null;
    }

    /**
     * Store a local image copy to a destination path.
     *
     * @throws ImageException
     */
    public static function storeLocalSource(string $source, string $destination = ''): void
    {
        if (! $destination) {
            $source = $destination;
        }

        $maxCachedImageSize = Cms::config()->maxCachedCloudImageSize;

        // Resize if constrained by maxCachedImageSizes setting
        if ($maxCachedImageSize > 0 && ImageHelper::canManipulateAsImage(pathinfo($source, PATHINFO_EXTENSION))) {
            $image = app(Images::class)->loadImage($source);

            if ($image instanceof Raster) {
                $image->setQuality(100);
            }

            $image->scaleToFit($maxCachedImageSize, $maxCachedImageSize, false)->saveAs($destination);
        } elseif ($source !== $destination) {
            copy($source, $destination);
        }
    }

    /**
     * Generates an image transform for an asset.
     *
     * @param  Asset  $asset  The asset
     * @param  ImageTransform  $transform  The image transform
     * @param  callable|null  $heartbeat  A callback that should be called while the transform is being generated
     * @param  Image|null  $image  The image object loaded for the transform
     *
     * @param-out Image $image The image object loaded for the transform
     *
     * @return string The temp path that the transform was saved to
     *
     * @throws ImageTransformException if the transform couldn't be generated.
     */
    public static function generateTransform(
        Asset $asset,
        ImageTransform $transform,
        ?callable $heartbeat = null,
        ?Image &$image = null,
    ): string {
        $ext = strtolower($asset->getExtension());

        if (! ImageHelper::canManipulateAsImage($ext)) {
            throw new ImageTransformException("Transforming .$ext files is not supported.");
        }

        $format = $transform->format ?: self::detectTransformFormat($asset);
        $imagesService = app(Images::class);

        $supported = match ($format) {
            Format::ID_WEBP => $imagesService->getSupportsWebP(),
            Format::ID_AVIF => $imagesService->getSupportsAvif(),
            Format::ID_HEIC => $imagesService->getSupportsHeic(),
            default => true,
        };

        if (! $supported) {
            throw new ImageTransformException("The `$format` format is not supported on this server.");
        }

        $imageSource = self::getLocalImageSource($asset);

        if ($ext === 'svg' && $format !== 'svg') {
            $size = max($transform->width, $transform->height) ?? 1000;
            $image = $imagesService->loadImage($imageSource, true, $size);
        } else {
            $image = $imagesService->loadImage($imageSource);
        }

        if ($image instanceof Raster) {
            $image->setQuality($transform->quality ?: Cms::config()->defaultImageQuality);
            $image->setHeartbeatCallback($heartbeat);
        }

        if ($asset->getHasFocalPoint() && $transform->mode === 'crop') {
            $position = $asset->getFocalPoint();
        } elseif (preg_match('/^(top|center|bottom)-(left|center|right)$/', $transform->position)) {
            $position = $transform->position;
        } else {
            $position = 'center-center';
        }

        $scaleIfSmaller = $transform->upscale ?? Cms::config()->upscaleImages;

        switch ($transform->mode) {
            case 'letterbox':
                if ($image instanceof Raster) {
                    $image->scaleToFitAndFill(
                        $transform->width,
                        $transform->height,
                        $transform->fill,
                        $position,
                        $scaleIfSmaller
                    );
                } else {
                    Log::info('Cannot add fill to non-raster images');
                    $image->scaleToFit($transform->width, $transform->height, $scaleIfSmaller);
                }
                break;
            case 'fit':
                $image->scaleToFit($transform->width, $transform->height, $scaleIfSmaller);
                break;
            case 'stretch':
                $image->resize($transform->width, $transform->height);
                break;
            default:
                $image->scaleAndCrop($transform->width, $transform->height, $scaleIfSmaller, $position);
        }

        if ($image instanceof Raster) {
            $image->setInterlace($transform->interlace);
        }

        // Save it!

        // It's important that the temp filename has the target file extension, as CraftCms\Cms\Image\Raster::saveAs() uses it
        // to determine the options that should be passed to Imagine\Image\ManipulatorInterface::save().
        $tempFilename = File::uniqueName(sprintf('%s.%s', $asset->getFilename(false), $format));
        $tempPath = Path::temp($tempFilename);
        $image->saveAs($tempPath);
        clearstatcache(true, $tempPath);

        return $tempPath;
    }
}
