<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\dates\DateTime;
use craft\app\elements\Asset;
use craft\app\errors\VolumeObjectExistsException;
use craft\app\helpers\Assets as AssetsHelper;
use craft\app\helpers\Db;
use craft\app\helpers\Image;
use craft\app\helpers\Io;
use craft\app\helpers\StringHelper;
use craft\app\image\Raster;
use craft\app\models\AssetTransformIndex;
use craft\app\models\AssetTransform as AssetTransformModel;
use craft\app\records\AssetTransform as AssetTransformRecord;
use craft\app\errors\AssetTransformException;
use craft\app\errors\VolumeObjectNotFoundException;
use craft\app\errors\VolumeException;
use craft\app\errors\AssetLogicException;
use craft\app\errors\ValidationException;
use Exception;
use yii\base\Application;
use yii\base\Component;

/**
 * Class AssetTransforms service.
 *
 * An instance of the AssetTransforms service is globally accessible in Craft via [[Application::assetTransforms `Craft::$app->getAssetTransforms()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AssetTransforms extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_transformsByHandle;

    /**
     * @var bool
     */
    private $_fetchedAllTransforms = false;

    /**
     * @var array
     */
    private $_sourcesToBeDeleted = [];

    // Public Methods
    // =========================================================================

    /**
     * Returns all named asset transforms.
     *
     * @param string|null $indexBy
     *
     * @return array
     */
    public function getAllTransforms($indexBy = null)
    {
        if (!$this->_fetchedAllTransforms) {
            $results = $this->_createTransformQuery()->all();

            $this->_transformsByHandle = [];

            foreach ($results as $result) {
                $transform = AssetTransformModel::create($result);
                $this->_transformsByHandle[$transform->handle] = $transform;
            }

            $this->_fetchedAllTransforms = true;
        }

        if ($indexBy == 'handle') {
            $transforms = $this->_transformsByHandle;
        } else {
            if (!$indexBy) {
                $transforms = array_values($this->_transformsByHandle);
            } else {
                $transforms = [];

                foreach ($this->_transformsByHandle as $transform) {
                    $transforms[$transform->$indexBy] = $transform;
                }
            }
        }

        return $transforms;
    }

    /**
     * Returns an asset transform by its handle.
     *
     * @param string $handle
     *
     * @return AssetTransformModel|null
     */
    public function getTransformByHandle($handle)
    {
        // If we've already fetched all transforms we can save ourselves a trip to the DB for transform handles that
        // don't exist
        if (!$this->_fetchedAllTransforms &&
            (!isset($this->_transformsByHandle) || !array_key_exists($handle,
                    $this->_transformsByHandle))
        ) {
            $result = $this->_createTransformQuery()
                ->where('handle = :handle', [':handle' => $handle])
                ->one();

            if ($result) {
                $transform = AssetTransformModel::create($result);
            } else {
                $transform = null;
            }

            $this->_transformsByHandle[$handle] = $transform;
        }

        if (isset($this->_transformsByHandle[$handle])) {
            return $this->_transformsByHandle[$handle];
        }
    }

    /**
     * Saves an asset transform.
     *
     * @param AssetTransformModel $transform
     *
     * @throws AssetTransformException If attempting to update a non-existing transform.
     * @throws ValidationException     If the validation failed.
     * @return boolean
     */
    public function saveTransform(AssetTransformModel $transform)
    {
        if ($transform->id) {
            $transformRecord = AssetTransformRecord::findOne($transform->id);

            if (!$transformRecord) {
                throw new AssetTransformException(Craft::t('app',
                    'Can’t find the transform with ID “{id}”',
                    ['id' => $transform->id]));
            }
        } else {
            $transformRecord = new AssetTransformRecord();
        }

        $transformRecord->name = $transform->name;
        $transformRecord->handle = $transform->handle;

        $heightChanged = $transformRecord->width != $transform->width || $transformRecord->height != $transform->height;
        $modeChanged = $transformRecord->mode != $transform->mode || $transformRecord->position != $transform->position;
        $qualityChanged = $transformRecord->quality != $transform->quality;

        if ($heightChanged || $modeChanged || $qualityChanged) {
            $transformRecord->dimensionChangeTime = new DateTime('@'.time());
        }

        $transformRecord->mode = $transform->mode;
        $transformRecord->position = $transform->position;
        $transformRecord->width = $transform->width;
        $transformRecord->height = $transform->height;
        $transformRecord->quality = $transform->quality;
        $transformRecord->format = $transform->format;

        $recordValidates = $transformRecord->validate();

        if ($recordValidates) {
            $transformRecord->save(false);

            // Now that we have a transform ID, save it on the model
            if (!$transform->id) {
                $transform->id = $transformRecord->id;
            }

            return true;
        } else {
            $transform->addErrors($transformRecord->getErrors());
            $exception = new ValidationException(Craft::t('app',
                'There were errors while saving the Asset Transform.'));
            $exception->setModel($transform);

            throw $exception;
        }
    }

    /**
     * Deletes an asset transform by it's id.
     *
     * @param integer $transformId
     *
     * @return boolean
     */
    public function deleteTransform($transformId)
    {
        Craft::$app->getDb()->createCommand()
            ->delete(
                '{{%assettransforms}}',
                ['id' => $transformId])
            ->execute();

        return true;
    }

    /**
     * Get a transform index row. If it doesn't exist - create one.
     *
     * @param Asset $asset
     * @param string $transform
     *
     * @return AssetTransformIndex
     */
    public function getTransformIndex(Asset $asset, $transform)
    {
        $transform = $this->normalizeTransform($transform);
        $transformLocation = $this->_getTransformFolderName($transform);

        // Check if an entry exists already
        $query = (new Query())
            ->select('ti.*')
            ->from('{{%assettransformindex}} ti')
            ->where('ti.volumeId = :volumeId AND ti.assetId = :assetId AND ti.location = :location',
                [
                    ':volumeId' => $asset->volumeId,
                    ':assetId' => $asset->id,
                    ':location' => $transformLocation
                ]);

        if (is_null($transform->format)) {
            // A generated auto-transform will have it's format set to null, but the filename will be populated.
            $query->andWhere('format IS NULL');
        } else {
            $query->andWhere('format = :format',
                [':format' => $transform->format]);
        }

        $entry = $query->one();

        if ($entry) {
            // If the file has been indexed after any changes impacting the transform, return the record
            $indexedAfterFileModified = $entry['dateIndexed'] >= Db::prepareDateForDb($asset->dateModified);
            $indexedAfterTransformParameterChange =
                (!$transform->isNamedTransform()
                    || ($transform->isNamedTransform()
                        && $entry['dateIndexed'] >= Db::prepareDateForDb($transform->dimensionChangeTime)));

            if ($indexedAfterFileModified && $indexedAfterTransformParameterChange) {
                return AssetTransformIndex::create($entry);
            } else {
                // Delete the out-of-date record
                Craft::$app->getDb()->createCommand()
                    ->delete(
                        '{{%assettransformindex}}',
                        'id = :transformIndexId',
                        [':transformIndexId' => $entry['id']])
                    ->execute();
            }
        }

        // Create a new record
        $time = new DateTime();
        $data = [
            'assetId' => $asset->id,
            'format' => $transform->format,
            'volumeId' => $asset->volumeId,
            'dateIndexed' => Db::prepareDateForDb($time),
            'location' => $transformLocation,
            'fileExists' => 0,
            'inProgress' => 0
        ];

        return $this->storeTransformIndexData(AssetTransformIndex::create($data));
    }

    /**
     * Get a transform URL by the transform index model.
     *
     * @param AssetTransformIndex $index
     *
     * @throws AssetTransformException If there was an error generating the transform.
     * @return string
     */
    public function ensureTransformUrlByIndexModel(AssetTransformIndex $index)
    {
        // Make sure we're not in the middle of working on this transform from a separate request
        if ($index->inProgress) {
            for ($safety = 0; $safety < 100; $safety++) {
                // Wait a second!
                sleep(1);
                ini_set('max_execution_time', 120);

                $index = $this->getTransformIndexModelById($index->id);

                // Is it being worked on right now?
                if ($index->inProgress) {
                    // Make sure it hasn't been working for more than 30 seconds. Otherwise give up on the other request.
                    $time = new DateTime();

                    if ($time->getTimestamp() - $index->dateUpdated->getTimestamp() < 30) {
                        continue;
                    } else {
                        $index->dateUpdated = new DateTime();
                        $this->storeTransformIndexData($index);
                        break;
                    }
                } else {
                    // Must be done now!
                    break;
                }
            }
        }

        if (!$index->fileExists) {
            // Mark the transform as in progress
            $index->inProgress = 1;
            $this->storeTransformIndexData($index);

            // Generate the transform
            if ($this->_generateTransform($index)) {
                // Update the index
                $index->inProgress = 0;
                $index->fileExists = 1;
                $this->storeTransformIndexData($index);
            } else {
                throw new AssetTransformException(Craft::t('app',
                    'Failed to generate transform with id of {id}.',
                    ['id' => $index->id]));
            }
        }

        return $this->getUrlForTransformByIndexId($index->id);
    }

    /**
     * Generate a transform by a created index.
     *
     * @param AssetTransformIndex $index
     *
     * @return bool true if transform exists for the index
     */
    private function _generateTransform(AssetTransformIndex $index)
    {
        // For _widthxheight_mode
        if (preg_match('/_(?P<width>[0-9]+|AUTO)x(?P<height>[0-9]+|AUTO)_(?P<mode>[a-z]+)_(?P<position>[a-z\-]+)(_(?P<quality>[0-9]+))?/i',
            $index->location, $matches)) {
            $transform = new AssetTransformModel();
            $transform->width = ($matches['width'] != 'AUTO' ? $matches['width'] : null);
            $transform->height = ($matches['height'] != 'AUTO' ? $matches['height'] : null);
            $transform->mode = $matches['mode'];
            $transform->position = $matches['position'];
            $transform->quality = isset($matches['quality']) ? $matches['quality'] : null;
        } else {
            // Load the dimensions for named transforms and merge with file-specific information.
            $transform = $this->normalizeTransform(mb_substr($index->location,
                1));
        }

        $index->transform = $transform;

        $asset = Craft::$app->getAssets()->getAssetById($index->assetId);
        $volume = $asset->getVolume();
        $index->detectedFormat = !empty($index->format) ? $index->format : $this->detectAutoTransformFormat($asset);

        $transformFilename = Io::getFilename($asset->filename,
                false).'.'.$index->detectedFormat;
        $index->filename = $transformFilename;

        $matchFound = false;

        // If the detected format matches the file's format, we can use the old-style formats as well so we can dig
        // through existing files. Otherwise, delete all transforms, records of it and create new.
        if ($asset->getExtension() == $index->detectedFormat) {
            $possibleLocations = [$this->_getUnnamedTransformFolderName($transform)];

            if ($transform->isNamedTransform()) {
                $possibleLocations[] = $this->_getNamedTransformFolderName($transform);
            }

            // We're looking for transforms that fit the bill and are not the one we are trying to find/create
            // the image for.
            $results = (new Query())
                ->select('*')
                ->from('{{%assettransformindex}}')
                ->where('assetId = :assetId', [':assetId' => $asset->id])
                ->andWhere(['in', 'location', $possibleLocations])
                ->andWhere('id <> :indexId', [':indexId' => $index->id])
                ->andWhere('fileExists = 1')
                ->all();

            foreach ($results as $result) {
                // If this is a named transform and indexed before dimensions last changed, this is a stale transform
                // and needs to go.
                if ($transform->isNamedTransform() && $result['dateIndexed'] < $transform->dimensionChangeTime) {
                    $transformUri = $asset->getFolder()->path.$this->getTransformSubpath($asset,
                            AssetTransformIndex::create($result));
                    $volume->deleteFile($transformUri);
                    $this->deleteTransformIndex($result['id']);
                } // Any other should do.
                else {
                    $matchFound = $result;
                }
            }
        }

        // If we have a match, copy the file.
        if ($matchFound) {
            /** @var array $matchFound */
            $from = $asset->getFolder()->path.$this->getTransformSubpath($asset,
                    AssetTransformIndex::create($matchFound));
            $to = $asset->getFolder()->path.$this->getTransformSubpath($asset,
                    $index);

            // Sanity check
            if ($volume->fileExists($to)) {
                return true;
            }

            $volume->copyFile($from, $to);
        } else {
            $this->_createTransformForAsset($asset, $index);
        }

        return $volume->fileExists($asset->getFolder()->path.$this->getTransformSubpath($asset,
                $index));
    }

    /**
     * Normalize a transform from handle or a set of properties to an AssetTransformModel.
     *
     * @param mixed $transform
     *
     * @throws AssetTransformException If the transform cannot be found by the handle.
     * @return AssetTransformModel|null
     */
    public function normalizeTransform($transform)
    {
        if (!$transform) {
            return null;
        } else {
            if (is_string($transform)) {
                $transformModel = $this->getTransformByHandle($transform);

                if ($transformModel) {
                    return $transformModel;
                }

                throw new AssetTransformException(Craft::t('app',
                    'The transform “{handle}” cannot be found!',
                    ['handle' => $transform]));
            } else {
                if ($transform instanceof AssetTransformModel) {
                    return $transform;
                } else {
                    if (is_object($transform) || is_array($transform)) {
                        return AssetTransformModel::create($transform);
                    } else {
                        return null;
                    }
                }
            }
        }
    }

    /**
     * Store a transform index data by it's model.
     *
     * @param AssetTransformIndex $index
     *
     * @return AssetTransformIndex
     */
    public function storeTransformIndexData(AssetTransformIndex $index)
    {
        $values = Db::prepareValuesForDb(
            $index->toArray([
                'assetId',
                'filename',
                'format',
                'location',
                'volumeId',
                'fileExists',
                'inProgress',
                'dateIndexed',
            ], [], false)
        );

        $dbConnection = Craft::$app->getDb();
        if (!empty($index->id)) {
            $dbConnection->createCommand()
                ->update('{{%assettransformindex}}', $values, ['id' => $index->id])
                ->execute();
        } else {
            $dbConnection->createCommand()
                ->insert('{{%assettransformindex}}', $values)
                ->execute();
            $index->id = $dbConnection->getLastInsertID();
        }

        return $index;
    }

    /**
     * Returns a list of pending transform index IDs.
     *
     * @return array
     */
    public function getPendingTransformIndexIds()
    {
        return (new Query())
            ->select('id')
            ->from('{{%assettransformindex}}')
            ->where(['and', 'fileExists = 0', 'inProgress = 0'])
            ->column();
    }

    /**
     * Get a transform index model by a row id.
     *
     * @param integer $transformId
     *
     * @return AssetTransformIndex|null
     */
    public function getTransformIndexModelById($transformId)
    {
        // Check if an entry exists already
        $entry = (new Query())
            ->select('ti.*')
            ->from('{{%assettransformindex}} ti')
            ->where('ti.id = :id', [':id' => $transformId])
            ->one();

        if ($entry) {
            return AssetTransformIndex::create($entry);
        }

        return null;
    }

    /**
     * Get a transform index model by a row id.
     *
     * @param integer $assetId
     * @param string $transformHandle
     *
     * @return AssetTransformIndex|null
     */
    public function getTransformIndexModelByAssetIdAndHandle(
        $assetId,
        $transformHandle
    ) {
        // Check if an entry exists already
        $entry = (new Query())
            ->select('ti.*')
            ->from('{{%assettransformindex}} ti')
            ->where('ti.assetId = :assetId AND ti.location = :location',
                [':assetId' => $assetId, ':location' => '_'.$transformHandle])
            ->one();

        if ($entry) {
            return AssetTransformIndex::create($entry);
        }

        return null;
    }

    /**
     * Get URL for Transform by TransformIndexId.
     *
     * @param $transformId
     *
     * @return string
     */
    public function getUrlForTransformByIndexId($transformId)
    {
        $transformIndexModel = $this->getTransformIndexModelById($transformId);

        $asset = Craft::$app->getAssets()->getAssetById($transformIndexModel->assetId);
        return $this->getUrlForTransformByAssetAndTransformIndex($asset,
            $transformIndexModel);
    }

    /**
     * Get URL for Transform by the transform index model.
     *
     * @param Asset $asset
     * @param AssetTransformIndex $transformIndexModel
     * @return string
     */
    public function getUrlForTransformByAssetAndTransformIndex(
        Asset $asset,
        AssetTransformIndex $transformIndexModel
    ) {
        $volume = $asset->getVolume();
        $baseUrl = $volume->getRootUrl();
        $appendix = AssetsHelper::getUrlAppendix($volume, $asset);

        return $baseUrl.$asset->getFolder()->path.$this->getTransformSubpath($asset,
            $transformIndexModel).$appendix;
    }

    /**
     * Delete transform records by an Asset id
     *
     * @param integer $assetId
     *
     * @return void
     */
    public function deleteTransformIndexDataByAssetId($assetId)
    {
        Craft::$app->getDb()->createCommand()
            ->delete('{{%assettransformindex}}', ['assetId' => $assetId])
            ->execute();
    }

    /**
     * Delete a transform index by.
     *
     * @param integer $indexId
     *
     * @return void
     */
    public function deleteTransformIndex($indexId)
    {
        Craft::$app->getDb()->createCommand()
            ->delete('{{%assettransformindex}}', ['id' => $indexId])
            ->execute();
    }

    /**
     * Get a thumb server path by Asset model and size.
     *
     * @param Asset $asset
     * @param       $size
     *
     * @return boolean|string
     */
    public function getResizedAssetServerPath(Asset $asset, $size)
    {
        $thumbFolder = Craft::$app->getPath()->getResizedAssetsPath().'/'.$size.'/';
        Io::ensureFolderExists($thumbFolder);

        $extension = $this->_getThumbExtension($asset);

        $thumbPath = $thumbFolder.$asset->id.'.'.$extension;

        if (!Io::fileExists($thumbPath)) {
            $imageSource = $this->getLocalImageSource($asset);

            Craft::$app->getImages()->loadImage($imageSource, false, $size)
                ->scaleToFit($size, $size)
                ->saveAs($thumbPath);

            if (!$asset->getVolume()->isLocal()) {
                $this->queueSourceForDeletingIfNecessary($imageSource);
            }
        }

        return $thumbPath;
    }

    /**
     * Get a local image source to use for transforms.
     *
     * @param Asset $asset
     *
     * @throws VolumeObjectNotFoundException If the file cannot be found.
     * @throws VolumeException               If there was an error downloading the remote file.
     * @return mixed
     */
    public function getLocalImageSource(Asset $asset)
    {
        $volume = $asset->getVolume();

        $imageSourcePath = $asset->getImageTransformSourcePath();

        if (!$volume->isLocal()) {
            if (!Io::fileExists($imageSourcePath) || Io::getFileSize($imageSourcePath) == 0) {
                if ($volume->isLocal()) {
                    throw new VolumeObjectNotFoundException(Craft::t('Image “{file}” cannot be found.',
                        ['file' => $asset->filename]));
                }

                // Delete it just in case it's a 0-byter
                Io::deleteFile($imageSourcePath, true);

                $localCopy = Io::getTempFilePath($asset->getExtension());

                $volume->saveFileLocally($asset->getUri(), $localCopy);

                if (!Io::fileExists($localCopy) || Io::getFileSize($localCopy) == 0) {
                    Io::deleteFile($localCopy, true);
                    throw new VolumeException(Craft::t('Tried to download the source file for image “{file}”, but it was 0 bytes long.',
                        ['file' => $asset->filename]));
                }

                $this->storeLocalSource($localCopy, $imageSourcePath);

                // Delete the leftover data.
                $this->queueSourceForDeletingIfNecessary($imageSourcePath);
                Io::deleteFile($localCopy, true);
            }
        }

        $asset->setTransformSource($imageSourcePath);

        return $imageSourcePath;
    }

    /**
     * Get the size of max cached cloud images dimension.
     *
     * @return integer
     */
    public function getCachedCloudImageSize()
    {
        return (int)Craft::$app->getConfig()->get('maxCachedCloudImageSize');
    }

    /**
     * Deletes an image local source if required by config.
     *
     * @param $imageSource
     *
     * @return void
     */
    public function queueSourceForDeletingIfNecessary($imageSource)
    {
        if (!($this->getCachedCloudImageSize() > 0)) {
            $this->_sourcesToBeDeleted[] = $imageSource;

            if (count($this->_sourcesToBeDeleted) == 1) {
                Craft::$app->on(Application::EVENT_AFTER_REQUEST,
                    [$this, 'deleteQueuedSourceFiles']);
            }
        }
    }

    /**
     * Store a local image copy to a destination path.
     *
     * @param $source
     * @param $destination
     *
     * @return void
     */
    public function storeLocalSource($source, $destination = '')
    {
        if (!$destination) {
            $source = $destination;
        }

        $maxCachedImageSize = $this->getCachedCloudImageSize();

        // Resize if constrained by maxCachedImageSizes setting
        if ($maxCachedImageSize > 0 && Image::isImageManipulatable(Io::getExtension($source))) {

            $image = Craft::$app->getImages()->loadImage($source);

            if ($image instanceof Raster) {
                $image->setQuality(100);
            }

            $image->scaleToFit($maxCachedImageSize,
                $maxCachedImageSize)->saveAs($destination);
        } else {
            if ($source != $destination) {
                Io::copyFile($source, $destination);
            }
        }
    }

    /**
     * Detect the auto web-safe format for the Asset. Returns null, if the Asset is not an image.
     *
     * @param Asset $asset
     *
     * @throws AssetLogicException If attempting to detect an image format for a non-image.
     * @return mixed|string
     */
    public function detectAutoTransformFormat(Asset $asset)
    {
        if (in_array(mb_strtolower($asset->getExtension()),
            Image::getWebSafeFormats())) {
            return $asset->getExtension();
        } else {
            if ($asset->kind == 'image') {

                // The only reasonable way to check for transparency is with Imagick. If Imagick is not present, then
                // we fallback to jpg
                $images = Craft::$app->getImages();
                if ($images->isGd() || !method_exists('Imagick',
                        'getImageAlphaChannel')
                ) {
                    return 'jpg';
                }

                $volume = $asset->getVolume();

                $path = Io::getTempFilePath($asset->getExtension());
                $localCopy = $volume->saveFileLocally($asset->getUri(), $path);

                $image = $images->loadImage($localCopy);

                if ($image->isTransparent()) {
                    $format = 'png';
                } else {
                    $format = 'jpg';
                }

                if (!$volume->isLocal()) {
                    // Store for potential later use and queue for deletion if needed.
                    $asset->setTransformSource($localCopy);
                    $this->queueSourceForDeletingIfNecessary($localCopy);
                } else {
                    // For local, though, we just delete the temp file.
                    Io::deleteFile($localCopy);
                }

                return $format;
            }
        }

        throw new AssetLogicException(Craft::t('app',
            'Tried to detect the appropriate image format for a non-image!'));
    }

    /**
     * Return a subfolder used by the Transform Index for the Asset.
     *
     * @param Asset $asset
     * @param AssetTransformIndex $index
     *
     * @return mixed|string
     */
    public function getTransformSubfolder(
        Asset $asset,
        AssetTransformIndex $index
    ) {
        $path = $index->location;

        if (!empty($index->filename) && $index->filename != $asset->filename) {
            $path .= '/'.$asset->id;
        }

        return $path;
    }

    /**
     * Return the filename used by the Transform Index for the Asset.
     *
     * @param Asset $asset
     * @param AssetTransformIndex $index
     *
     * @return mixed
     */
    public function getTransformFilename(
        Asset $asset,
        AssetTransformIndex $index
    ) {
        if (empty($index->filename)) {
            return $asset->filename;
        } else {
            return $index->filename;
        }
    }

    /**
     * Get a transform subpath used by the Transform Index for the Asset.
     *
     * @param Asset $asset
     * @param AssetTransformIndex $index
     *
     * @return string
     */
    public function getTransformSubpath(
        Asset $asset,
        AssetTransformIndex $index
    ) {
        return $this->getTransformSubfolder($asset,
            $index).'/'.$this->getTransformFilename($asset, $index);
    }

    /**
     * Delete *ALL* transform data (including thumbs and sources) associated with the Asset.
     *
     * @param Asset $asset
     *
     * @return void
     */
    public function deleteAllTransformData(Asset $asset)
    {
        $this->deleteResizedAssetVersion($asset);
        $this->deleteCreatedTransformsForAsset($asset);
        $this->deleteTransformIndexDataByAssetId($asset->id);

        Io::deleteFile(Craft::$app->getPath()->getAssetsImageSourcePath().'/'.$asset->id.'.'.Io::getExtension($asset->filename),
            true);
    }

    /**
     * Delete all the generated thumbnails for the Asset.
     *
     * @param Asset $asset
     *
     * @return void
     */
    public function deleteResizedAssetVersion(Asset $asset)
    {
        $thumbFolders = Io::getFolderContents(Craft::$app->getPath()->getResizedAssetsPath());

        foreach ($thumbFolders as $folder) {
            if (is_dir($folder)) {
                Io::deleteFile($folder.'/'.$asset->id.'.'.$this->_getThumbExtension($asset),
                    true);
            }
        }
    }

    /**
     * Delete created transforms for an Asset.
     *
     * @param Asset $asset
     */
    public function deleteCreatedTransformsForAsset(Asset $asset)
    {
        $transformIndexes = $this->getAllCreatedTransformsForAsset($asset);

        $volume = $asset->getVolume();

        foreach ($transformIndexes as $transformIndex) {
            $volume->deleteFile($asset->getFolder()->path.$this->getTransformSubpath($asset,
                    $transformIndex));
        }
    }

    /**
     * Get an array of AssetTransformIndex models for all created transforms for an Asset.
     *
     * @param Asset $asset
     *
     * @return array
     */
    public function getAllCreatedTransformsForAsset(Asset $asset)
    {
        $transforms = (new Query())
            ->select('*')
            ->from('{{%assettransformindex}}')
            ->where('assetId = :assetId', [':assetId' => $asset->id])
            ->all();

        foreach ($transforms as $key => $value) {
            $transforms[$key] = AssetTransformIndex::create($value);
        }

        return $transforms;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving transforms.
     *
     * @return Query
     */
    private function _createTransformQuery()
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'mode',
                'position',
                'height',
                'width',
                'format',
                'quality',
                'dimensionChangeTime'
            ])
            ->from('{{%assettransforms}}')
            ->orderBy('name');
    }

    /**
     * Returns a transform's folder name.
     *
     * @param AssetTransformModel $transform
     *
     * @return string
     */
    private function _getTransformFolderName(AssetTransformModel $transform)
    {
        if ($transform->isNamedTransform()) {
            return $this->_getNamedTransformFolderName($transform);
        } else {
            return $this->_getUnnamedTransformFolderName($transform);
        }
    }

    /**
     * Returns a named transform's folder name.
     *
     * @param AssetTransformModel $transform
     *
     * @return string
     */
    private function _getNamedTransformFolderName(AssetTransformModel $transform
    ) {
        return '_'.$transform->handle;
    }

    /**
     * Returns an unnamed transform's folder name.
     *
     * @param AssetTransformModel $transform
     *
     * @return string
     */
    private function _getUnnamedTransformFolderName(
        AssetTransformModel $transform
    ) {
        return '_'.($transform->width ? $transform->width : 'AUTO').'x'.($transform->height ? $transform->height : 'AUTO').
        '_'.($transform->mode).
        '_'.($transform->position).
        ($transform->quality ? '_'.$transform->quality : '');
    }

    /**
     * Create a transform for the Asset by the transform index.
     *
     * @param Asset $asset
     * @param AssetTransformIndex $index
     *
     * @throws AssetTransformException If a transform index has an invalid transform assigned.
     * @return void
     */
    private function _createTransformForAsset(
        Asset $asset,
        AssetTransformIndex $index
    ) {
        if (!Image::isImageManipulatable(Io::getExtension($asset->filename))) {
            return;
        }

        if (empty($index->transform)) {
            $transform = $this->normalizeTransform(mb_substr($index->location,
                1));

            if (empty($transform)) {
                throw new AssetTransformException(Craft::t('app',
                    'Unable to recognize the transform for this transform index!'));
            }
        } else {
            $transform = $index->transform;
        }

        if (!isset($index->detectedFormat)) {
            $index->detectedFormat = !empty($index->format) ? $index->format : $this->detectAutoTransformFormat($asset);
        }

        $volume = $asset->getVolume();
        $transformPath = $asset->getFolder()->path.$this->getTransformSubpath($asset,
                $index);

        // Already created. Relax, grasshopper!
        if ($volume->fileExists($transformPath)) {
            return;
        }

        $imageSource = $asset->getTransformSource();
        $quality = $transform->quality ? $transform->quality : Craft::$app->getConfig()->get('defaultImageQuality');

        $images = Craft::$app->getImages();
        if (StringHelper::toLowerCase($asset->getExtension()) == 'svg' && $index->detectedFormat != 'svg') {
            $image = $images->loadImage($imageSource, true,
                max($transform->width, $transform->height));
        } else {
            $image = $images->loadImage($imageSource);
        }

        if ($image instanceof Raster) {
            $image->setQuality($quality);
        }

        switch ($transform->mode) {
            case 'fit': {
                $image->scaleToFit($transform->width, $transform->height);
                break;
            }

            case 'stretch': {
                $image->resize($transform->width, $transform->height);
                break;
            }

            default: {
                if (!preg_match('/(top|center|bottom)-(left|center|right)/',
                    $transform->position)
                ) {
                    $transform->position = 'center-center';
                }

                $image->scaleAndCrop($transform->width, $transform->height,
                    true, $transform->position);
                break;
            }
        }

        $createdTransform = Io::getTempFilePath($index->detectedFormat);
        $image->saveAs($createdTransform);

        clearstatcache(true, $createdTransform);

        $stream = fopen($createdTransform, "r");

        try {
            $volume->createFileByStream($transformPath, $stream);
        } catch (VolumeObjectExistsException $e) {
            // We're fine with that.
        }

        Io::deleteFile($createdTransform);

        if (!$asset->getVolume()->isLocal()) {
            $this->queueSourceForDeletingIfNecessary($imageSource);
        }

        return;
    }

    /**
     * Return the thumbnail extension for a asset.
     *
     * @param Asset $asset
     *
     * @return string
     */
    private function _getThumbExtension(Asset $asset)
    {
        // For non-web-safe formats we go with jpg.
        if (!in_array(
            mb_strtolower(Io::getExtension($asset->filename)),
            Image::getWebSafeFormats()
        )
        ) {
            return 'jpg';
        } else {
            return $asset->getExtension();
        }
    }
}
