<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\assets\imagetransforms;

use Craft;
use craft\base\ImageTransformDriver;
use craft\base\LocalFsInterface;
use craft\elements\Asset;
use craft\errors\ImageTransformException;
use craft\errors\VolumeException;
use craft\events\GenerateTransformEvent;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\ImageTransforms as TransformHelper;
use craft\image\Raster;
use craft\models\ImageTransformIndex;
use craft\services\ImageTransforms;

/**
 * DefaultDriver transforms the images using the Imagine library.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class DefaultDriver extends ImageTransformDriver
{
    /**
     * A list of files to be deleted when request ends
     * @var array
     */
    private array $_filesToBeDeleted = [];

    /**
     * Returns the URL for an image asset transform.11111
     *
     * @return string The URL for the transform
     */
    public function getTransformUrl(Asset $asset, ImageTransformIndex $transformIndexModel): string
    {
        if ($transformIndexModel->fileExists) {
            $fs = $asset->getVolume()->getFilesystem();
            $uri = $this->getTransformUri($asset, $transformIndexModel);

            // Check if it really exists
            if ($fs instanceof LocalFsInterface && !$fs->fileExists($asset->folderPath . $uri)) {
                $transformIndexModel->fileExists = false;
                Craft::$app->getImageTransforms()->storeTransformIndexData($transformIndexModel);
            } else {
                return AssetsHelper::generateUrl($asset->getVolume(), $asset, $uri, $transformIndexModel);
            }
        }

        return $this->ensureTransformUrlByIndexModel($asset, $transformIndexModel);
    }

    /**
     * @inheritdoc
     */
    public function invalidateTransform(Asset $asset, ImageTransformIndex $transformIndex): void
    {
        $volume = $asset->getVolume();
        $volume->deleteFile($asset->folderPath . $this->getTransformSubpath($asset, $transformIndex));
    }

    /**
     * Return a subfolder used by the Transform Index for the Asset.
     *
     * @param Asset $asset
     * @param ImageTransformIndex $index
     * @return string
     */
    public function getTransformSubfolder(Asset $asset, ImageTransformIndex $index): string
    {
        $path = $index->transformString;

        if (!empty($index->filename) && $index->filename !== $asset->getFilename()) {
            $path .= DIRECTORY_SEPARATOR . $asset->id;
        }

        return $path;
    }

    /**
     * Return the filename used by the Transform Index for the Asset.
     *
     * @param Asset $asset
     * @param ImageTransformIndex $index
     * @return string
     */
    public function getTransformFilename(Asset $asset, ImageTransformIndex $index): string
    {
        return $index->filename ?: $asset->getFilename();
    }

    /**
     * Returns the path to a transform, relative to the asset's folder.
     *
     * @param Asset $asset
     * @param ImageTransformIndex $index
     * @return string
     */
    public function getTransformSubpath(Asset $asset, ImageTransformIndex $index): string
    {
        return $this->getTransformSubfolder($asset, $index) . DIRECTORY_SEPARATOR . $this->getTransformFilename($asset, $index);
    }

    /**
     * Returns the URI for a transform, relative to the asset's folder.
     *
     * @param Asset $asset
     * @param ImageTransformIndex $index
     * @return string
     */
    public function getTransformUri(Asset $asset, ImageTransformIndex $index): string
    {
        $uri = $this->getTransformSubpath($asset, $index);

        if (DIRECTORY_SEPARATOR !== '/') {
            $uri = str_replace(DIRECTORY_SEPARATOR, '/', $uri);
        }

        return $uri;
    }

    /**
     * Create a transform for the Asset by the transform index.
     *
     * @param Asset $asset
     * @param ImageTransformIndex $index
     * @throws ImageTransformException If a transform index has an invalid transform assigned.
     */
    private function _createTransformForAsset(Asset $asset, ImageTransformIndex $index): void
    {
        if (!Image::canManipulateAsImage(pathinfo($asset->getFilename(), PATHINFO_EXTENSION))) {
            return;
        }

        $transform = $index->getTransform();
        $images = Craft::$app->getImages();

        if ($index->format === 'webp' && !$images->getSupportsWebP()) {
            throw new ImageTransformException("The `webp` format is not supported on this server!");
        }

        $volume = $asset->getVolume();
        $transformPath = $asset->folderPath . $this->getTransformSubpath($asset, $index);

        // Already created. Relax, grasshopper!
        if ($volume->fileExists($transformPath)) {
            $dateModified = $volume->getDateModified($transformPath);
            $parameterChangeTime = $index->getTransform()->parameterChangeTime;

            if (!$parameterChangeTime || $parameterChangeTime->getTimestamp() <= $dateModified) {
                return;
            }

            // Let's cook up a new one.
            try {
                $volume->deleteFile($transformPath);
            } catch (\Throwable $exception) {
                // Unlikely, but if it got deleted while we were comparing timestamps, don't freak out.
            }
        }

        $imageSource = TransformHelper::getLocalImageSource($asset);
        $quality = $transform->quality ?: Craft::$app->getConfig()->getGeneral()->defaultImageQuality;

        if (strtolower($asset->getExtension()) === 'svg' && $index->detectedFormat !== 'svg') {
            $image = $images->loadImage($imageSource, true, max($transform->width, $transform->height));
        } else {
            $image = $images->loadImage($imageSource);
        }

        if ($image instanceof Raster) {
            $image->setQuality($quality);
        }

        // In case this takes a while, update the timestamp so we know it's all working
        $image->setHeartbeatCallback(fn () => Craft::$app->getImageTransforms()->storeTransformIndexData($index));

        switch ($transform->mode) {
            case 'fit':
                $image->scaleToFit($transform->width, $transform->height);
                break;
            case 'stretch':
                $image->resize($transform->width, $transform->height);
                break;
            default:
                if ($asset->getHasFocalPoint()) {
                    $position = $asset->getFocalPoint();
                } else if (!preg_match('/(top|center|bottom)-(left|center|right)/', $transform->position)) {
                    $position = 'center-center';
                } else {
                    $position = $transform->position;
                }
                $image->scaleAndCrop($transform->width, $transform->height, Craft::$app->getConfig()->getGeneral()->upscaleImages, $position);
        }

        if ($image instanceof Raster) {
            $image->setInterlace($transform->interlace);
        }


        $event = new GenerateTransformEvent([
            'transformIndex' => $index,
            'asset' => $asset,
            'image' => $image,
        ]);

        Craft::$app->getImageTransforms()->trigger(ImageTransforms::EVENT_GENERATE_TRANSFORM, $event);

        if ($event->tempPath !== null) {
            $tempPath = $event->tempPath;
        } else {
            $tempFilename = uniqid(pathinfo($index->filename, PATHINFO_FILENAME), true) . '.' . $index->detectedFormat;
            $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;
            $image->saveAs($tempPath);
        }

        clearstatcache(true, $tempPath);

        $stream = fopen($tempPath, 'rb');

        try {
            $volume->writeFileFromStream($transformPath, $stream, []);
        } catch (VolumeException $e) {
            Craft::$app->getErrorHandler()->logException($e);
        }

        FileHelper::unlink($tempPath);

        $volume = $asset->getVolume();
    }

    /**
     * Generate a transform by a created index.
     *
     * @param ImageTransformIndex $index
     * @return bool true if transform exists for the index
     * @throws ImageTransformException
     */
    protected function generateTransform(ImageTransformIndex $index): bool
    {
        $transformService = Craft::$app->getImageTransforms();
        $asset = Craft::$app->getAssets()->getAssetById($index->assetId);
        $volume = $asset->getVolume();

        $index->detectedFormat = $index->format ?: TransformHelper::detectTransformFormat($asset);
        $transformFilename = pathinfo($asset->getFilename(), PATHINFO_FILENAME) . '.' . $index->detectedFormat;
        $index->filename = $transformFilename;

        $matchFound = $transformService->getSimilarTransformIndex($asset, $index);

        // If we have a match, copy the file.
        if ($matchFound) {
            $from = $asset->folderPath . $this->getTransformSubpath($asset, $matchFound);
            $to = $asset->folderPath . $this->getTransformSubpath($asset, $index);

            // Sanity check
            try {
                if ($volume->fileExists($to)) {
                    return true;
                }

                $volume->copyFile($from, $to);
            } catch (VolumeException $exception) {
                throw new ImageTransformException('There was a problem re-using an existing transform.', 0, $exception);
            }
        } else {
            $this->_createTransformForAsset($asset, $index);
        }

        return $volume->fileExists($asset->folderPath . $this->getTransformSubpath($asset, $index));
    }
}
