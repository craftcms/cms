<?php
declare(strict_types = 1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\imagetransforms;

use Craft;
use craft\base\imagetransforms\EagerImageTransformerInterface;
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\base\LocalFsInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\errors\FsException;
use craft\errors\ImageTransformException;
use craft\events\GenerateTransformEvent;
use craft\gql\types\DateTime;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\ImageTransforms as TransformHelper;
use craft\helpers\Queue;
use craft\helpers\UrlHelper;
use craft\image\Raster;
use craft\models\ImageTransform;
use craft\models\ImageTransformIndex;
use craft\queue\jobs\GeneratePendingTransforms;
use craft\services\ImageTransforms;
use yii\base\InvalidConfigException;

/**
 * ImageTransformer transforms image assets using GD or ImageMagick.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ImageTransformer implements ImageTransformerInterface, EagerImageTransformerInterface
{
    /**
     * @var ImageTransformIndex[]
     */
    protected array $eagerLoadedTransformIndexes = [];

    /**
     * Returns the URL for an image asset transform.
     *
     * @return string The URL for the transform
     */
    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
    {
        if (!$immediately) {
            return $this->_deferredTransformUrl($asset, $imageTransform);
        }

        $imageTransformIndex = $this->getTransformIndex($asset, $imageTransform);

        if ($imageTransformIndex->fileExists) {
            $fs = $asset->getFs();
            $uri = $this->getTransformUri($asset, $imageTransformIndex);

            // Check if it really exists
            if ($fs instanceof LocalFsInterface && !$fs->fileExists($asset->folderPath . $uri)) {
                $imageTransformIndex->fileExists = false;
                $this->storeTransformIndexData($imageTransformIndex);
            } else {
                return AssetsHelper::generateUrl($asset->getVolume(), $asset, $uri, $imageTransformIndex->dateUpdated);
            }
        }

        return $this->ensureTransformUrlByIndexModel($asset, $imageTransformIndex);
    }

    /**
     * @inheritdoc
     */
    public function invalidateAssetTransforms(Asset $asset): void
    {
        $transformIndexes = $this->getAllCreatedTransformsForAsset($asset);

        foreach ($transformIndexes as $transformIndex) {
            $this->deleteImageTransform($asset, $transformIndex);
        }

        $this->deleteTransformIndexDataByAssetId($asset->id);
    }

    /**
     * @param Asset $asset
     * @param ImageTransformIndex $transformIndex
     * @throws InvalidConfigException
     */
    protected function deleteImageTransform(Asset $asset, ImageTransformIndex $transformIndex): void
    {
        $asset->getFs()->deleteFile($asset->folderPath . $this->getTransformSubpath($asset, $transformIndex));
    }

    /**
     * @inheritdoc
     */
    private function _deferredTransformUrl(Asset $asset, ImageTransform $imageTransform): string
    {
        $index = $this->getTransformIndex($asset, $imageTransform);

        // Does the file actually exist?
        if ($index->fileExists) {
            return $this->getTransformUrl($asset, $imageTransform, true);
        }

        static $queued = null;

        if (!$queued) {
            Queue::push(new GeneratePendingTransforms(), 2048);
            $queued = true;
        }

        // Return the temporary transform URL
        return UrlHelper::actionUrl('assets/generate-transform', ['transformId' => $index->id], null, false);
    }

    /**
     * @inheritdoc
     */
    public function eagerLoadTransforms(array $transforms, array $assets): void
    {
        // Index the assets by ID
        $assetsById = ArrayHelper::index($assets, 'id');
        $indexCondition = ['or'];
        $transformsByFingerprint = [];

        foreach ($transforms as $transform) {
            $transformString = $fingerprint = TransformHelper::getTransformString($transform);

            if ($transform->format !== null) {
                $fingerprint .= ':' . $transform->format;
            }

            $transformsByFingerprint[$fingerprint] = $transform;
            $transformCondition = ['and', ['transformString' => $transformString]];

            if ($transform->format === null) {
                $transformCondition[] = ['format' => null];
            } else {
                $transformCondition[] = ['format' => $transform->format];
                $fingerprint .= ':' . $transform->format;
            }

            $indexCondition[] = $transformCondition;
            $transformsByFingerprint[$fingerprint] = $transform;
        }

        // Query for the indexes
        $results = $this->_createTransformIndexQuery()
            ->where([
                'and',
                ['assetId' => array_keys($assetsById)],
                $indexCondition,
            ])
            ->all();

        // Index the valid transform indexes by fingerprint, and capture the IDs of indexes that should be deleted
        $invalidIndexIds = [];

        foreach ($results as $result) {
            // Get the transform's fingerprint
            $transformFingerprint = $result['transformString'];

            if ($result['format']) {
                $transformFingerprint .= ':' . $result['format'];
            }

            // Is it still valid?
            $transform = $transformsByFingerprint[$transformFingerprint];
            $asset = $assetsById[$result['assetId']];

            if ($this->validateTransformIndexResult($result, $transform, $asset)) {
                $indexFingerprint = $result['assetId'] . ':' . $transformFingerprint;
                $this->eagerLoadedTransformIndexes[$indexFingerprint] = $result;
            } else {
                $invalidIndexIds[] = $result['id'];
            }
        }

        // Delete any invalid indexes
        if (!empty($invalidIndexIds)) {
            Db::delete(Table::IMAGETRANSFORMINDEX, [
                'id' => $invalidIndexIds,
            ], [], Craft::$app->getImageTransforms()->db);
        }
    }

    // Protected methods
    // =============================================================

    /**
     * Return a subfolder used by the Transform Index for the Asset.
     *
     * @param Asset $asset
     * @param ImageTransformIndex $transformIndex
     * @return string
     * @throws InvalidConfigException
     */
    protected function getTransformSubfolder(Asset $asset, ImageTransformIndex $transformIndex): string
    {
        $path = $transformIndex->transformString;

        if (!empty($transformIndex->filename) && $transformIndex->filename !== $asset->getFilename()) {
            $path .= DIRECTORY_SEPARATOR . $asset->id;
        }

        return $path;
    }

    /**
     * Return the filename used by the Transform Index for the Asset.
     *
     * @param Asset $asset
     * @param ImageTransformIndex $transformIndex
     * @return string
     * @throws InvalidConfigException
     */
    protected function getTransformFilename(Asset $asset, ImageTransformIndex $transformIndex): string
    {
        return $transformIndex->filename ?: $asset->getFilename();
    }

    /**
     * Returns the path to a transform, relative to the asset's folder.
     *
     * @param Asset $asset
     * @param ImageTransformIndex $transformIndex
     * @return string
     * @throws InvalidConfigException
     */
    protected function getTransformSubpath(Asset $asset, ImageTransformIndex $transformIndex): string
    {
        return $this->getTransformSubfolder($asset, $transformIndex) . DIRECTORY_SEPARATOR . $this->getTransformFilename($asset, $transformIndex);
    }

    /**
     * Returns the URI for a transform, relative to the asset's folder.
     *
     * @param Asset $asset
     * @param ImageTransformIndex $index
     * @return string
     */
    protected function getTransformUri(Asset $asset, ImageTransformIndex $index): string
    {
        $uri = $this->getTransformSubpath($asset, $index);

        if (DIRECTORY_SEPARATOR !== '/') {
            $uri = str_replace(DIRECTORY_SEPARATOR, '/', $uri);
        }

        return $uri;
    }

    /**
     * Generate the actual image for the Asset by the transform index.
     *
     * @param Asset $asset
     * @param ImageTransformIndex $index
     * @throws ImageTransformException If a transform index has an invalid transform assigned.
     */
    protected function generateTransformedImage(Asset $asset, ImageTransformIndex $index): void
    {
        if (!Image::canManipulateAsImage(pathinfo($asset->getFilename(), PATHINFO_EXTENSION))) {
            return;
        }

        $transform = $index->getTransform();
        $images = Craft::$app->getImages();

        if ($index->format === 'webp' && !$images->getSupportsWebP()) {
            throw new ImageTransformException("The `webp` format is not supported on this server!");
        }

        if ($index->format === 'avif' && !$images->getSupportsAvif()) {
            throw new ImageTransformException("The `avif` format is not supported on this server!");
        }

        $volume = $asset->getVolume();
        $transformPath = $asset->folderPath . $this->getTransformSubpath($asset, $index);

        // Already created. Relax, grasshopper!
        if ($volume->getFs()->fileExists($transformPath)) {
            $dateModified = $volume->getFs()->getDateModified($transformPath);
            $parameterChangeTime = $index->getTransform()->parameterChangeTime;

            if (!$parameterChangeTime || $parameterChangeTime->getTimestamp() <= $dateModified) {
                return;
            }

            // Let's cook up a new one.
            try {
                $volume->getFs()->deleteFile($transformPath);
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
        $image->setHeartbeatCallback(fn() => $this->storeTransformIndexData($index));

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
            $volume->getFs()->writeFileFromStream($transformPath, $stream, []);
        } catch (FsException $e) {
            Craft::$app->getErrorHandler()->logException($e);
        }

        FileHelper::unlink($tempPath);
    }

    /**
     * Check if a transformed image exists. If it does not, attempt to generate it.
     *
     * @param ImageTransformIndex $index
     * @return bool true if transform exists for the index
     * @throws ImageTransformException
     */
    protected function procureTransformedImage(ImageTransformIndex $index): bool
    {
        $asset = Craft::$app->getAssets()->getAssetById($index->assetId);

        if (!$asset) {
            throw new ImageTransformException('Asset not found - ' . $index->assetId);
        }

        $volume = $asset->getVolume();

        $index->detectedFormat = $index->format ?: TransformHelper::detectTransformFormat($asset);
        $transformFilename = pathinfo($asset->getFilename(), PATHINFO_FILENAME) . '.' . $index->detectedFormat;
        $index->filename = $transformFilename;

        $matchFound = $this->getSimilarTransformIndex($asset, $index);
        $fs = $volume->getFs();

        // If we have a match, copy the file.
        if ($matchFound) {
            $from = $asset->folderPath . $this->getTransformSubpath($asset, $matchFound);
            $to = $asset->folderPath . $this->getTransformSubpath($asset, $index);

            // Sanity check
            try {
                if ($fs->fileExists($to)) {
                    return true;
                }

                $fs->copyFile($from, $to);
            } catch (FsException $exception) {
                throw new ImageTransformException('There was a problem re-using an existing transform.', 0, $exception);
            }
        } else {
            $this->generateTransformedImage($asset, $index);
        }

        return $fs->fileExists($asset->folderPath . $this->getTransformSubpath($asset, $index));
    }

    /**
     * Get a transform URL by the transform index model.
     *
     * @param Asset $asset
     * @param ImageTransformIndex $index
     * @return string
     * @throws ImageTransformException If there was an error generating the transform.
     */
    protected function ensureTransformUrlByIndexModel(Asset $asset, ImageTransformIndex $index): string
    {
        // Make sure we're not in the middle of working on this transform from a separate request
        if ($index->inProgress) {
            for ($safety = 0; $safety < 100; $safety++) {

                if ($index->error) {
                    throw new ImageTransformException(Craft::t('app',
                        'Failed to generate transform with id of {id}.',
                        ['id' => $index->id]));
                }

                // Wait a second!
                sleep(1);
                App::maxPowerCaptain();

                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $index = $this->getTransformIndexModelById($index->id);

                // Is it being worked on right now?
                if ($index->inProgress) {
                    // Make sure it hasn't been working for more than 30 seconds. Otherwise give up on the other request.
                    $time = new \DateTime();

                    if ($time->getTimestamp() - $index->dateUpdated->getTimestamp() < 30) {
                        continue;
                    }

                    $this->storeTransformIndexData($index);
                    break;
                }

                // Must be done now!
                break;
            }
        }

        // No file, then
        if (!$index->fileExists) {
            // Mark the transform as in progress
            $index->inProgress = true;
            $this->storeTransformIndexData($index);

            // Generate the transform
            try {
                if ($this->procureTransformedImage($index)) {
                    // Update the index
                    $index->inProgress = false;
                    $index->fileExists = true;
                } else {
                    $index->inProgress = false;
                    $index->fileExists = false;
                    $index->error = true;
                }

                $this->storeTransformIndexData($index);
            } catch (\Exception $e) {
                $index->inProgress = false;
                $index->fileExists = false;
                $index->error = true;
                $this->storeTransformIndexData($index);
                Craft::$app->getErrorHandler()->logException($e);

                throw new ImageTransformException(Craft::t('app',
                    'Failed to generate transform with id of {id}.',
                    ['id' => $index->id]));
            }
        }

        return $this->getTransformUrl($asset, $index->getTransform(), true);
    }

    /**
     * Get a transform index row. If it doesn't exist - create one.
     *
     * @param Asset $asset
     * @param ImageTransform|string|array|null $transform
     * @return ImageTransformIndex
     * @throws ImageTransformException if the transform cannot be found by the handle
     */
    protected function getTransformIndex(Asset $asset, $transform): ImageTransformIndex
    {
        $transform = TransformHelper::normalizeTransform($transform);

        if ($transform === null) {
            throw new ImageTransformException('There was a problem finding the transform.');
        }

        $transformString = TransformHelper::getTransformString($transform);

        // Was it eager-loaded?
        $fingerprint = $asset->id . ':' . $transformString . ($transform->format === null ? '' : ':' . $transform->format);

        if (isset($this->eagerLoadedTransformIndexes[$fingerprint])) {
            $result = $this->eagerLoadedTransformIndexes[$fingerprint];
            return new ImageTransformIndex($result);
        }

        // Check if an entry exists already
        $query = $this->_createTransformIndexQuery()
            ->where([
                'assetId' => $asset->id,
                'transformString' => $transformString,
            ]);

        if ($transform->format === null) {
            // A generated auto-transform will have its format set to null, but the filename will be populated.
            $query->andWhere(['format' => null]);
        } else {
            $query->andWhere(['format' => $transform->format]);
        }

        $result = $query->one();

        if ($result) {
            $existingIndex = new ImageTransformIndex($result);

            if ($this->validateTransformIndexResult($result, $transform, $asset)) {
                return $existingIndex;
            }

            // Delete the out-of-date record
            Db::delete(Table::IMAGETRANSFORMINDEX, [
                'id' => $result['id'],
            ], [], Craft::$app->getImageTransforms()->db);

            // And the generated transform itself, too
            $this->deleteImageTransform($asset, $existingIndex);
        }

        // Create a new record
        $newIndex = new ImageTransformIndex([
            'assetId' => $asset->id,
            'format' => $transform->format,
            'transformer' => $transform->getTransformer(),
            'dateIndexed' => Db::prepareDateForDb(new DateTime()),
            'transformString' => $transformString,
            'fileExists' => false,
            'inProgress' => false,
        ]);


        if ($transform instanceof ImageTransform) {
            $newIndex->setTransform($transform);
        }

        return $this->storeTransformIndexData($newIndex);
    }

    /**
     * Validates a transform index result to see if the index is still valid for a given asset.
     *
     * @param array $result
     * @param ImageTransform $transform
     * @param Asset|array $asset The asset object or a raw database result
     * @return bool Whether the index result is still valid
     */
    protected function validateTransformIndexResult(array $result, ImageTransform $transform, $asset): bool
    {
        // If the transform hasn't been generated yet, it's probably not yet invalid.
        if (empty($result['dateIndexed'])) {
            return true;
        }

        // If the asset has been modified since the time the index was created, it's no longer valid
        $dateModified = ArrayHelper::getValue($asset, 'dateModified');
        if ($result['dateIndexed'] < Db::prepareDateForDb($dateModified)) {
            return false;
        }

        // If it's not a named transform, consider it valid
        if (!$transform->getIsNamedTransform()) {
            return true;
        }

        // If the named transform's dimensions have changed since the time the index was created, it's no longer valid
        if ($result['dateIndexed'] < Db::prepareDateForDb($transform->parameterChangeTime)) {
            return false;
        }

        return true;
    }

    /**
     * Store a transform index data by it's model.
     *
     * @param ImageTransformIndex $index
     * @return ImageTransformIndex
     */
    protected function storeTransformIndexData(ImageTransformIndex $index): ImageTransformIndex
    {
        $values = Db::prepareValuesForDb(
            $index->toArray([
                'assetId',
                'transformer',
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

        $db = Craft::$app->getImageTransforms()->db;
        if ($index->id !== null) {
            Db::update(Table::IMAGETRANSFORMINDEX, $values, [
                'id' => $index->id,
            ], [], true, $db);
        } else {
            Db::insert(Table::IMAGETRANSFORMINDEX, $values, $db);
            $index->id = (int)$db->getLastInsertID(Table::IMAGETRANSFORMINDEX);
        }

        return $index;
    }

    /**
     * Returns a list of pending transform index IDs.
     *
     * @return array
     */
    public function getPendingTransformIndexIds(): array
    {
        return $this->_createTransformIndexQuery()
            ->select(['id'])
            ->where(['fileExists' => false, 'inProgress' => false])
            ->column();
    }

    /**
     * Get a transform index model by a row id.
     *
     * @param int $transformId
     * @return ImageTransformIndex|null
     */
    public function getTransformIndexModelById(int $transformId): ?ImageTransformIndex
    {
        $result = $this->_createTransformIndexQuery()
            ->where(['id' => $transformId])
            ->one();

        return $result ? new ImageTransformIndex($result) : null;
    }

    /**
     * Delete transform records by an Asset id
     *
     * @param int $assetId
     */
    protected function deleteTransformIndexDataByAssetId(int $assetId): void
    {
        Db::delete(Table::IMAGETRANSFORMINDEX, [
            'assetId' => $assetId,
        ], [], Craft::$app->getImageTransforms()->db);
    }

    /**
     * Get an array of ImageTransformIndex models for all created transforms for an Asset.
     *
     * @param Asset $asset
     * @return ImageTransformIndex[]
     */
    protected function getAllCreatedTransformsForAsset(Asset $asset): array
    {
        $results = $this->_createTransformIndexQuery()
            ->where(['assetId' => $asset->id])
            ->all();

        foreach ($results as $key => $result) {
            $results[$key] = new ImageTransformIndex($result);
        }

        return $results;
    }

    /**
     * Find a similar image transform for reuse for an asset and existing transform.
     *
     * @param Asset $asset
     * @param ImageTransformIndex $index
     * @return ImageTransformIndex|null
     * @throws InvalidConfigException
     */
    protected function getSimilarTransformIndex(Asset $asset, ImageTransformIndex $index): ?ImageTransformIndex
    {
        $transform = $index->getTransform();
        $result = null;

        if ($asset->getExtension() === $index->detectedFormat && !$asset->getHasFocalPoint()) {
            $possibleLocations = [TransformHelper::getTransformString($transform, true)];

            if ($transform->getIsNamedTransform()) {
                $namedLocation = TransformHelper::getTransformString($transform);
                $possibleLocations[] = $namedLocation;
            }

            // We're looking for transforms that fit the bill and are not the one we are trying to find/create
            // the image for.
            $result = $this->_createTransformIndexQuery()
                ->where([
                    'and',
                    [
                        'assetId' => $asset->id,
                        'fileExists' => true,
                        'transformString' => $possibleLocations,
                        'format' => $index->detectedFormat,
                    ],
                    ['not', ['id' => $index->id]],
                ])
                ->one();
        }

        return $result ? Craft::createObject(array_merge(['class' => ImageTransformIndex::class], $result)) : null;
    }

    /**
     * Returns a Query object prepped for retrieving transform indexes.
     *
     * @return Query
     */
    private function _createTransformIndexQuery(): Query
    {
        return (new Query())
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
            ])
            ->from([Table::IMAGETRANSFORMINDEX]);
    }
}
