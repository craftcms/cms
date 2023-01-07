<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\LocalFsInterface;
use craft\elements\Asset;
use craft\errors\AssetException;
use craft\errors\AssetOperationException;
use craft\errors\FsException;
use craft\errors\FsObjectNotFoundException;
use craft\errors\ImageException;
use craft\errors\ImageTransformException;
use craft\image\Raster;
use craft\models\ImageTransform;

/**
 * Image Transforms helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ImageTransforms
{
    /**
     * @var string The pattern to use for matching against a transform string.
     */
    public const TRANSFORM_STRING_PATTERN = '/_(?P<width>\d+|AUTO)x(?P<height>\d+|AUTO)_(?P<mode>[a-z]+)(?:_(?P<position>[a-z\-]+))?(?:_(?P<quality>\d+))?(?:_(?P<interlace>[a-z]+))?/i';

    /**
     * Create an AssetImageTransform model from a string.
     *
     * @param string $transformString
     * @return ImageTransform
     */
    public static function createTransformFromString(string $transformString): ImageTransform
    {
        if (!preg_match(self::TRANSFORM_STRING_PATTERN, $transformString, $matches)) {
            throw new ImageTransformException('Cannot create a transform from string: ' . $transformString);
        }

        if ($matches['width'] == 'AUTO') {
            unset($matches['width']);
        }
        if ($matches['height'] == 'AUTO') {
            unset($matches['height']);
        }

        if (empty($matches['quality'])) {
            unset($matches['quality']);
        }

        return Craft::createObject([
            'class' => ImageTransform::class,
            'width' => $matches['width'] ?? null,
            'height' => $matches['height'] ?? null,
            'mode' => $matches['mode'],
            'position' => $matches['position'],
            'quality' => $matches['quality'] ?? null,
            'interlace' => $matches['interlace'] ?? 'none',
            'transformer' => ImageTransform::DEFAULT_TRANSFORMER,
        ]);
    }

    /**
     * Detect the auto web-safe format for the Asset. Returns null, if the Asset is not an image.
     *
     * @param Asset $asset
     * @return string
     * @throws AssetOperationException If attempting to detect an image format for a non-image.
     */
    public static function detectTransformFormat(Asset $asset): string
    {
        if (Image::isWebSafe($asset->getExtension())) {
            return $asset->getExtension();
        }

        if ($asset->kind !== Asset::KIND_IMAGE) {
            throw new AssetOperationException(Craft::t('app',
                'Tried to detect the appropriate image format for a non-image!'));
        }

        return 'jpg';
    }

    /**
     * Extend a transform by taking an existing transform and overriding its parameters.
     *
     * @param ImageTransform $transform
     * @param array $parameters
     * @return ImageTransform
     */
    public static function extendTransform(ImageTransform $transform, array $parameters): ImageTransform
    {
        if (!empty($parameters)) {
            // Don't change the same transform
            $transform = clone $transform;

            $whiteList = [
                'width',
                'height',
                'format',
                'mode',
                'format',
                'position',
                'quality',
                'interlace',
                'transformer',
            ];

            $nullables = [
                'id',
                'name',
                'handle',
                'uid',
                'parameterChangeTime',
            ];

            foreach ($parameters as $parameter => $value) {
                if (in_array($parameter, $whiteList, true)) {
                    /** @phpstan-ignore-next-line */
                    $transform->$parameter = $value;
                }
            }

            foreach ($nullables as $nullable) {
                $transform->{$nullable} = null;
            }
        }

        return $transform;
    }

    /**
     * Get a local image source to use for transforms.
     *
     * @param Asset $asset
     * @return string
     * @throws FsObjectNotFoundException If the file cannot be found.
     */
    public static function getLocalImageSource(Asset $asset): string
    {
        $volume = $asset->getVolume();
        $fs = $volume->getFs();

        $imageSourcePath = $asset->getImageTransformSourcePath();

        try {
            if (!$fs instanceof LocalFsInterface) {
                // This is a non-local fs
                if (!is_file($imageSourcePath) || filesize($imageSourcePath) === 0) {
                    if (is_file($imageSourcePath)) {
                        // Delete since it's a 0-byter
                        FileHelper::unlink($imageSourcePath);
                    }

                    $prefix = pathinfo($asset->getFilename(), PATHINFO_FILENAME) . '.delimiter.';
                    $extension = $asset->getExtension();
                    $tempFilename = uniqid($prefix, true) . '.' . $extension;
                    $tempPath = Craft::$app->getPath()->getTempPath();
                    $tempFilePath = $tempPath . DIRECTORY_SEPARATOR . $tempFilename;

                    // Fetch a list of existing temp files for this image.
                    $files = FileHelper::findFiles($tempPath, [
                        'only' => [
                            $prefix . '*' . '.' . $extension,
                        ],
                    ]);

                    // And clean them up.
                    if (!empty($files)) {
                        foreach ($files as $filePath) {
                            FileHelper::unlink($filePath);
                        }
                    }

                    Assets::downloadFile($fs, $asset->getPath(), $tempFilePath);

                    if (!is_file($tempFilePath) || filesize($tempFilePath) === 0) {
                        if (is_file($tempFilePath) && !FileHelper::unlink($tempFilePath)) {
                            Craft::warning("Unable to delete the file \"$tempFilePath\".", __METHOD__);
                        }
                        throw new FsException(Craft::t('app', 'Tried to download the source file for image “{file}”, but it was 0 bytes long.', [
                            'file' => $asset->getFilename(),
                        ]));
                    }

                    // we've downloaded the file, now store it
                    self::storeLocalSource($tempFilePath, $imageSourcePath);

                    // And delete it after the request, if nobody wants it.
                    if (Craft::$app->getConfig()->getGeneral()->maxCachedCloudImageSize == 0) {
                        FileHelper::deleteFileAfterRequest($imageSourcePath);
                    }

                    if (!FileHelper::unlink($tempFilePath)) {
                        Craft::warning("Unable to delete the file \"$tempFilePath\".", __METHOD__);
                    }
                }
            }
        } catch (AssetException) {
            // Make sure we throw a new exception
            $imageSourcePath = false;
        }

        if (!is_file($imageSourcePath)) {
            throw new FsObjectNotFoundException("The file \"{$asset->getFilename()}\" does not exist.");
        }

        return $imageSourcePath;
    }

    /**
     * Get the transform string for a given asset image transform.
     *
     * @param ImageTransform $transform
     * @param bool $ignoreHandle whether the transform handle should be ignored
     * @return string
     */
    public static function getTransformString(ImageTransform $transform, bool $ignoreHandle = false): string
    {
        if (!$ignoreHandle && !empty($transform->handle)) {
            return '_' . $transform->handle;
        }

        return '_' . ($transform->width ?: 'AUTO') . 'x' . ($transform->height ?: 'AUTO') .
            '_' . $transform->mode .
            '_' . $transform->position .
            ($transform->quality ? '_' . $transform->quality : '') .
            '_' . $transform->interlace;
    }

    /**
     * Normalize a transform from handle or a set of properties to an ImageTransform.
     *
     * @param mixed $transform
     * @return ImageTransform|null
     * @throws ImageTransformException if $transform is an invalid transform handle
     */
    public static function normalizeTransform(mixed $transform): ?ImageTransform
    {
        if (!$transform) {
            return null;
        }

        if ($transform instanceof ImageTransform) {
            return $transform;
        }

        if (is_object($transform)) {
            $transform = ArrayHelper::toArray($transform, [
                'id',
                'name',
                'transformer',
                'handle',
                'width',
                'height',
                'format',
                'parameterChangeTime',
                'mode',
                'position',
                'quality',
                'interlace',
            ]);
        }

        if (is_array($transform)) {
            if (!empty($transform['width']) && !is_numeric($transform['width'])) {
                Craft::warning("Invalid transform width: {$transform['width']}", __METHOD__);
                $transform['width'] = null;
            }

            if (!empty($transform['height']) && !is_numeric($transform['height'])) {
                Craft::warning("Invalid transform height: {$transform['height']}", __METHOD__);
                $transform['height'] = null;
            }

            if (array_key_exists('transform', $transform)) {
                $baseTransform = self::normalizeTransform(ArrayHelper::remove($transform, 'transform'));
                return self::extendTransform($baseTransform, $transform);
            }

            return new ImageTransform($transform);
        }

        if (is_string($transform)) {
            if (preg_match(self::TRANSFORM_STRING_PATTERN, $transform)) {
                return self::createTransformFromString($transform);
            }

            $transform = StringHelper::removeLeft($transform, '_');
            if (($transformModel = Craft::$app->getImageTransforms()->getTransformByHandle($transform)) === null) {
                throw new ImageTransformException(Craft::t('app', 'Invalid transform handle: {handle}', ['handle' => $transform]));
            }

            return $transformModel;
        }

        return null;
    }

    /**
     * Store a local image copy to a destination path.
     *
     * @param string $source
     * @param string $destination
     * @throws ImageException
     */
    public static function storeLocalSource(string $source, string $destination = ''): void
    {
        if (!$destination) {
            $source = $destination;
        }

        $maxCachedImageSize = Craft::$app->getConfig()->getGeneral()->maxCachedCloudImageSize;

        // Resize if constrained by maxCachedImageSizes setting
        if ($maxCachedImageSize > 0 && Image::canManipulateAsImage(pathinfo($source, PATHINFO_EXTENSION))) {
            $image = Craft::$app->getImages()->loadImage($source);

            if ($image instanceof Raster) {
                $image->setQuality(100);
            }

            $image->scaleToFit($maxCachedImageSize, $maxCachedImageSize, false)->saveAs($destination);
        } else {
            if ($source !== $destination) {
                copy($source, $destination);
            }
        }
    }
}
