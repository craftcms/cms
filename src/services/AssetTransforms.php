<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\LocalVolumeInterface;
use craft\base\MemoizableArray;
use craft\db\Connection;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\errors\AssetException;
use craft\errors\AssetLogicException;
use craft\errors\AssetTransformException;
use craft\errors\VolumeException;
use craft\errors\VolumeObjectExistsException;
use craft\errors\VolumeObjectNotFoundException;
use craft\events\AssetTransformEvent;
use craft\events\AssetTransformImageEvent;
use craft\events\ConfigEvent;
use craft\events\GenerateTransformEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\StringHelper;
use craft\image\Raster;
use craft\models\AssetTransform;
use craft\models\AssetTransformIndex;
use craft\records\AssetTransform as AssetTransformRecord;
use DateTime;
use yii\base\Application;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\di\Instance;

/**
 * Asset Transforms service.
 * An instance of the Asset Transforms service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getAssetTransforms()|`Craft::$app->assetTransforms`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AssetTransforms extends Component
{
    /**
     * @event AssetTransformEvent The event that is triggered before an asset transform is saved
     */
    const EVENT_BEFORE_SAVE_ASSET_TRANSFORM = 'beforeSaveAssetTransform';

    /**
     * @event AssetTransformEvent The event that is triggered after an asset transform is saved
     */
    const EVENT_AFTER_SAVE_ASSET_TRANSFORM = 'afterSaveAssetTransform';

    /**
     * @event AssetTransformEvent The event that is triggered before an asset transform is deleted
     */
    const EVENT_BEFORE_DELETE_ASSET_TRANSFORM = 'beforeDeleteAssetTransform';

    /**
     * @event AssetTransformEvent The event that is triggered after an asset transform is deleted
     */
    const EVENT_AFTER_DELETE_ASSET_TRANSFORM = 'afterDeleteAssetTransform';

    /**
     * @event GenerateTransformEvent The event that is triggered when a transform is being generated for an Asset.
     */
    const EVENT_GENERATE_TRANSFORM = 'generateTransform';

    /**
     * @event AssetTransformImageEvent The event that is triggered before deleting generated transforms.
     */
    const EVENT_BEFORE_DELETE_TRANSFORMS = 'beforeDeleteTransforms';

    /**
     * @event AssetTransformEvent The event that is triggered before a transform delete is applied to the database.
     * @since 3.1.0
     */
    const EVENT_BEFORE_APPLY_TRANSFORM_DELETE = 'beforeApplyTransformDelete';

    /**
     * @event AssetTransformImageEvent The event that is triggered after deleting generated transforms.
     */
    const EVENT_AFTER_DELETE_TRANSFORMS = 'afterDeleteTransforms';

    const CONFIG_TRANSFORM_KEY = 'imageTransforms';

    /**
     * @var Connection|array|string The database connection to use
     * @since 3.5.4
     */
    public $db = 'db';

    /**
     * @var MemoizableArray|null
     * @see _transforms()
     */
    private $_transforms;

    /**
     * @var array
     */
    private $_sourcesToBeDeleted = [];

    /**
     * @var array|null
     */
    private $_eagerLoadedTransformIndexes;

    /**
     * @var AssetTransformIndex|null
     */
    private $_activeTransformIndex;

    /**
     * Serializer
     *
     * @since 3.5.14
     */
    public function __serialize()
    {
        $vars = get_object_vars($this);
        unset($vars['_transforms']);
        return $vars;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::class);
    }

    /**
     * Returns a memoizable array of all named asset transforms.
     *
     * @return MemoizableArray
     */
    private function _transforms(): MemoizableArray
    {
        if ($this->_transforms === null) {
            $transforms = [];
            foreach ($this->_createTransformQuery()->all() as $result) {
                $transforms[] = new AssetTransform($result);
            }
            $this->_transforms = new MemoizableArray($transforms);
        }

        return $this->_transforms;
    }

    /**
     * Returns all named asset transforms.
     *
     * @return AssetTransform[]
     */
    public function getAllTransforms(): array
    {
        return $this->_transforms()->all();
    }

    /**
     * Returns an asset transform by its handle.
     *
     * @param string $handle
     * @return AssetTransform|null
     */
    public function getTransformByHandle(string $handle)
    {
        return $this->_transforms()->firstWhere('handle', $handle, true);
    }

    /**
     * Returns an asset transform by its ID.
     *
     * @param int $id
     * @return AssetTransform|null
     */
    public function getTransformById(int $id)
    {
        return $this->_transforms()->firstWhere('id', $id);
    }

    /**
     * Returns an asset transform by its UID.
     *
     * @param string $uid
     * @return AssetTransform|null
     * @since 3.1.0
     */
    public function getTransformByUid(string $uid)
    {
        return $this->_transforms()->firstWhere('uid', $uid, true);
    }

    /**
     * Saves an asset transform.
     *
     * @param AssetTransform $transform The transform to be saved
     * @param bool $runValidation Whether the transform should be validated
     * @return bool
     * @throws AssetTransformException If attempting to update a non-existing transform.
     */
    public function saveTransform(AssetTransform $transform, bool $runValidation = true): bool
    {
        $isNewTransform = !$transform->id;

        // Fire a 'beforeSaveAssetTransform' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_ASSET_TRANSFORM)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_ASSET_TRANSFORM, new AssetTransformEvent([
                'assetTransform' => $transform,
                'isNew' => $isNewTransform,
            ]));
        }

        if ($runValidation && !$transform->validate()) {
            Craft::info('Asset transform not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewTransform) {
            $transform->uid = StringHelper::UUID();
        } else if (!$transform->uid) {
            $transform->uid = Db::uidById(Table::ASSETTRANSFORMS, $transform->id, $this->db);
        }

        $projectConfig = Craft::$app->getProjectConfig();

        $configData = [
            'format' => $transform->format,
            'handle' => $transform->handle,
            'height' => (int)$transform->height ?: null,
            'interlace' => $transform->interlace,
            'mode' => $transform->mode,
            'name' => $transform->name,
            'position' => $transform->position,
            'quality' => (int)$transform->quality ?: null,
            'width' => (int)$transform->width ?: null,
        ];

        $configPath = self::CONFIG_TRANSFORM_KEY . '.' . $transform->uid;
        $projectConfig->set($configPath, $configData, "Saving transform “{$transform->handle}”");

        if ($isNewTransform) {
            $transform->id = Db::idByUid(Table::ASSETTRANSFORMS, $transform->uid, $this->db);
        }

        return true;
    }

    /**
     * Handle transform change.
     *
     * @param ConfigEvent $event
     */
    public function handleChangedTransform(ConfigEvent $event)
    {
        $transformUid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = $this->db->beginTransaction();
        $deleteTransformIndexes = false;

        try {
            $transformRecord = $this->_getTransformRecord($transformUid);
            $isNewTransform = $transformRecord->getIsNewRecord();

            $transformRecord->name = $data['name'];
            $transformRecord->handle = $data['handle'];

            $heightChanged = $transformRecord->width !== $data['width'] || $transformRecord->height !== $data['height'];
            $modeChanged = $transformRecord->mode !== $data['mode'] || $transformRecord->position !== $data['position'];
            $qualityChanged = $transformRecord->quality !== $data['quality'];
            $interlaceChanged = $transformRecord->interlace !== $data['interlace'];

            if ($heightChanged || $modeChanged || $qualityChanged || $interlaceChanged) {
                $transformRecord->dimensionChangeTime = new DateTime('@' . time());
                $deleteTransformIndexes = true;
            }

            $transformRecord->mode = $data['mode'];
            $transformRecord->position = $data['position'];
            $transformRecord->width = $data['width'];
            $transformRecord->height = $data['height'];
            $transformRecord->quality = $data['quality'];
            $transformRecord->interlace = $data['interlace'];
            $transformRecord->format = $data['format'];
            $transformRecord->uid = $transformUid;

            $transformRecord->save(false);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        if ($deleteTransformIndexes) {
            Db::delete(Table::ASSETTRANSFORMINDEX, [
                'location' => $this->_getNamedTransformFolderName($transformRecord->handle),
            ], [], $this->db);
        }

        // Clear caches
        $this->_transforms = null;

        // Fire an 'afterSaveAssetTransform' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_ASSET_TRANSFORM)) {
            $this->trigger(self::EVENT_AFTER_SAVE_ASSET_TRANSFORM, new AssetTransformEvent([
                'assetTransform' => $this->getTransformById($transformRecord->id),
                'isNew' => $isNewTransform,
            ]));
        }

        // Invalidate asset caches
        Craft::$app->getElements()->invalidateCachesForElementType(Asset::class);
    }

    /**
     * Deletes an asset transform by its ID.
     *
     * @param int $transformId The transform's ID
     * @return bool Whether the transform was deleted.
     * @throws \yii\db\Exception on DB error
     */
    public function deleteTransformById(int $transformId): bool
    {
        $transform = $this->getTransformById($transformId);

        if (!$transform) {
            return false;
        }

        return $this->deleteTransform($transform);
    }

    /**
     * Deletes an asset transform.
     *
     * Note that passing an ID to this function is now deprecated. Use [[deleteTransformById()]] instead.
     *
     * @param int|AssetTransform $transform The transform
     * @return bool Whether the transform was deleted
     * @throws \yii\db\Exception on DB error
     */
    public function deleteTransform($transform): bool
    {
        // todo: remove this code in 3.0 & hardcode the $transform type
        if (is_int($transform)) {
            Craft::$app->getDeprecator()->log(self::class . '::deleteTransform(id)', '`' . self::class . '::deleteTransform()` should only be called with a `' . AssetTransform::class . '` reference. Use `' . self::class . '::deleteTransformById()` to get a transform by its ID.');
            return $this->deleteTransformById($transform);
        }
        if (!$transform instanceof AssetTransform) {
            throw new InvalidArgumentException('$transform must be a ' . AssetTransform::class . ' object.');
        }

        // Fire a 'beforeDeleteAssetTransform' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_ASSET_TRANSFORM)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_ASSET_TRANSFORM, new AssetTransformEvent([
                'assetTransform' => $transform
            ]));
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_TRANSFORM_KEY . '.' . $transform->uid, "Delete transform “{$transform->handle}”");
        return true;
    }

    /**
     * Handle transform being deleted
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedTransform(ConfigEvent $event)
    {
        $transformUid = $event->tokenMatches[0];

        $transform = $this->getTransformByUid($transformUid);

        if (!$transform) {
            return;
        }

        // Fire a 'beforeApplyTransformDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_TRANSFORM_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_TRANSFORM_DELETE, new AssetTransformEvent([
                'assetTransform' => $transform,
            ]));
        }

        Db::delete(Table::ASSETTRANSFORMS, [
            'uid' => $transformUid,
        ], [], $this->db);

        // Clear caches
        $this->_transforms = null;

        // Fire an 'afterDeleteAssetTransform' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_ASSET_TRANSFORM)) {
            $this->trigger(self::EVENT_AFTER_DELETE_ASSET_TRANSFORM, new AssetTransformEvent([
                'assetTransform' => $transform
            ]));
        }

        // Invalidate asset caches
        Craft::$app->getElements()->invalidateCachesForElementType(Asset::class);
    }

    /**
     * Eager-loads transform indexes the given list of assets.
     *
     * You can include `srcset`-style sizes (e.g. `100w` or `2x`) following a normal transform definition, for example:
     *
     * ::: code
     *
     * ```twig
     * [{width: 1000, height: 600}, '1.5x', '2x', '3x']
     * ```
     *
     * ```php
     * [['width' => 1000, 'height' => 600], '1.5x', '2x', '3x']
     * ```
     *
     * :::
     *
     * When a `srcset`-style size is encountered, the preceding normal transform definition will be used as a
     * reference when determining the resulting transform dimensions.
     *
     * @param Asset[]|array $assets The assets or asset data to eager-load transforms for
     * @param array $transforms The transform definitions to eager-load
     */
    public function eagerLoadTransforms(array $assets, array $transforms)
    {
        if (empty($assets) || empty($transforms)) {
            return;
        }

        // Index the assets by ID
        $assetsById = ArrayHelper::index($assets, 'id');

        // Get the index conditions
        $transformsByFingerprint = [];
        $indexCondition = ['or'];

        /** @var AssetTransform|null $refTransform */
        $refTransform = null;

        foreach ($transforms as $transform) {
            // Is this a srcset-style size (2x, 100w, etc.)?
            try {
                [$sizeValue, $sizeUnit] = AssetsHelper::parseSrcsetSize($transform);
            } catch (InvalidArgumentException $e) {
                // All good.
            }

            if (isset($sizeValue, $sizeUnit)) {
                if ($refTransform === null || !$refTransform->width) {
                    throw new InvalidArgumentException("Can’t eager-load transform “{$transform}” without a prior transform that specifies the base width");
                }

                $transform = [];
                if ($sizeUnit === 'w') {
                    $transform['width'] = (int)$sizeValue;
                } else {
                    $transform['width'] = (int)ceil($refTransform->width * $sizeValue);
                }

                // Only set the height if the reference transform has a height set on it
                if ($refTransform && $refTransform->height) {
                    if ($sizeUnit === 'w') {
                        $transform['height'] = (int)ceil($refTransform->height * $transform['width'] / $refTransform->width);
                    } else {
                        $transform['height'] = (int)ceil($refTransform->height * $sizeValue);
                    }
                }
            }

            $transform = $this->normalizeTransform($transform);
            if ($transform === null) {
                continue;
            }

            $location = $fingerprint = $this->_getTransformFolderName($transform);

            $transformCondition = ['and', ['location' => $location]];

            if ($transform->format === null) {
                $transformCondition[] = ['format' => null];
            } else {
                $transformCondition[] = ['format' => $transform->format];
                $fingerprint .= ':' . $transform->format;
            }

            $indexCondition[] = $transformCondition;
            $transformsByFingerprint[$fingerprint] = $transform;

            if (!isset($sizeValue)) {
                // Use this as the reference transform in case any srcset-style transforms follow it
                $refTransform = $transform;
            }
        }

        unset($refTransform);

        // Query for the indexes
        $results = $this->_createTransformIndexQuery()
            ->where([
                'and',
                ['assetId' => array_keys($assetsById)],
                $indexCondition
            ])
            ->all();

        // Index the valid transform indexes by fingerprint, and capture the IDs of indexes that should be deleted
        $invalidIndexIds = [];

        foreach ($results as $result) {
            // Get the transform's fingerprint
            $transformFingerprint = $result['location'];

            if ($result['format']) {
                $transformFingerprint .= ':' . $result['format'];
            }

            // Is it still valid?
            $transform = $transformsByFingerprint[$transformFingerprint];
            $asset = $assetsById[$result['assetId']];

            if ($this->validateTransformIndexResult($result, $transform, $asset)) {
                $indexFingerprint = $result['assetId'] . ':' . $transformFingerprint;
                $this->_eagerLoadedTransformIndexes[$indexFingerprint] = $result;
            } else {
                $invalidIndexIds[] = $result['id'];
            }
        }

        // Delete any invalid indexes
        if (!empty($invalidIndexIds)) {
            Db::delete(Table::ASSETTRANSFORMINDEX, [
                'id' => $invalidIndexIds,
            ], [], $this->db);
        }
    }

    /**
     * Get a transform index row. If it doesn't exist - create one.
     *
     * @param Asset $asset
     * @param AssetTransform|string|array|null $transform
     * @return AssetTransformIndex
     * @throws AssetTransformException if the transform cannot be found by the handle
     */
    public function getTransformIndex(Asset $asset, $transform): AssetTransformIndex
    {
        $transform = $this->normalizeTransform($transform);

        if ($transform === null) {
            throw new AssetTransformException('There was a problem finding the transform.');
        }

        $transformLocation = $this->_getTransformFolderName($transform);

        // Was it eager-loaded?
        $fingerprint = $asset->id . ':' . $transformLocation . ($transform->format === null ? '' : ':' . $transform->format);

        if (isset($this->_eagerLoadedTransformIndexes[$fingerprint])) {
            $result = $this->_eagerLoadedTransformIndexes[$fingerprint];
            return new AssetTransformIndex($result);
        }

        // Check if an entry exists already
        $query = $this->_createTransformIndexQuery()
            ->where([
                'volumeId' => $asset->getVolumeId(),
                'assetId' => $asset->id,
                'location' => $transformLocation
            ]);

        if ($transform->format === null) {
            // A generated auto-transform will have it's format set to null, but the filename will be populated.
            $query->andWhere(['format' => null]);
        } else {
            $query->andWhere(['format' => $transform->format]);
        }

        $result = $query->one();

        if ($result) {
            if ($this->validateTransformIndexResult($result, $transform, $asset)) {
                return new AssetTransformIndex($result);
            }

            // Delete the out-of-date record
            Db::delete(Table::ASSETTRANSFORMINDEX, [
                'id' => $result['id'],
            ], [], $this->db);

            // And the file.
            $transformUri = $asset->folderPath . $this->getTransformSubpath($asset, new AssetTransformIndex($result));
            $asset->getVolume()->deleteFile($transformUri);
        }

        // Create a new record
        $transformIndex = new AssetTransformIndex([
            'assetId' => $asset->id,
            'format' => $transform->format,
            'volumeId' => $asset->getVolumeId(),
            'dateIndexed' => Db::prepareDateForDb(new DateTime()),
            'location' => $transformLocation,
            'fileExists' => false,
            'inProgress' => false
        ]);

        return $this->storeTransformIndexData($transformIndex);
    }

    /**
     * Validates a transform index result to see if the index is still valid for a given asset.
     *
     * @param array $result
     * @param AssetTransform $transform
     * @param Asset|array $asset The asset object or a raw database result
     * @return bool Whether the index result is still valid
     */
    public function validateTransformIndexResult(array $result, AssetTransform $transform, $asset): bool
    {
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
        if ($result['dateIndexed'] < Db::prepareDateForDb($transform->dimensionChangeTime)) {
            return false;
        }

        return true;
    }

    /**
     * Get a transform URL by the transform index model.
     *
     * @param AssetTransformIndex $index
     * @return string
     * @throws AssetTransformException If there was an error generating the transform.
     */
    public function ensureTransformUrlByIndexModel(AssetTransformIndex $index): string
    {
        // Make sure we're not in the middle of working on this transform from a separate request
        if ($index->inProgress) {
            for ($safety = 0; $safety < 100; $safety++) {

                if ($index->error) {
                    throw new AssetTransformException(Craft::t('app',
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
                    $time = new DateTime();

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

        if (!$index->fileExists) {
            // Mark the transform as in progress
            $index->inProgress = true;
            $this->storeTransformIndexData($index);

            // Generate the transform
            try {
                if ($this->_generateTransform($index)) {
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
     * @return bool true if transform exists for the index
     * @throws AssetTransformException
     */
    private function _generateTransform(AssetTransformIndex $index): bool
    {
        // For _widthxheight_mode
        if (preg_match('/_(?P<width>\d+|AUTO)x(?P<height>\d+|AUTO)_(?P<mode>[a-z]+)(?:_(?P<position>[a-z\-]+))?(?:_(?P<quality>\d+))?(?:_(?P<interlace>[a-z]+))?/i', $index->location, $matches)) {
            $transform = new AssetTransform();
            $transform->width = ($matches['width'] !== 'AUTO' ? (int)$matches['width'] : null);
            $transform->height = ($matches['height'] !== 'AUTO' ? (int)$matches['height'] : null);
            $transform->mode = $matches['mode'];
            $transform->position = $matches['position'];
            $transform->quality = isset($matches['quality']) ? (int)$matches['quality'] : null;
            $transform->interlace = $matches['interlace'] ?? 'none';
        } else {
            // Load the dimensions for named transforms and merge with file-specific information.
            $transform = $this->normalizeTransform(mb_substr($index->location, 1));

            if ($transform === null) {
                throw new AssetTransformException('There was a problem finding the transform.');
            }
        }

        $index->setTransform($transform);

        $asset = Craft::$app->getAssets()->getAssetById($index->assetId);
        $volume = $asset->getVolume();
        $index->detectedFormat = $index->format ?: $this->detectAutoTransformFormat($asset);

        $transformFilename = pathinfo($asset->filename, PATHINFO_FILENAME) . '.' . $index->detectedFormat;
        $index->filename = $transformFilename;

        $matchFound = false;

        // If the detected format matches the file's format, we can use the old-style formats as well so we can dig
        // through existing files. Otherwise, delete all transforms, records of it and create new.
        // Focal points make transforms non-reusable, though
        if ($asset->getExtension() === $index->detectedFormat && !$asset->getHasFocalPoint()) {
            $possibleLocations = [$this->_getUnnamedTransformFolderName($transform)];

            if ($transform->getIsNamedTransform()) {
                $namedLocation = $this->_getNamedTransformFolderName($transform);
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
                        'location' => $possibleLocations,
                        'format' => $index->detectedFormat,
                    ],
                    ['not', ['id' => $index->id]]
                ])
                ->one();

            if ($result) {
                $matchFound = $result;
            }
        }

        // If we have a match, copy the file.
        if ($matchFound) {
            /** @var array $matchFound */
            $from = $asset->folderPath . $this->getTransformSubpath($asset, new AssetTransformIndex($matchFound));
            $to = $asset->folderPath . $this->getTransformSubpath($asset, $index);

            // Sanity check
            if ($volume->fileExists($to)) {
                return true;
            }

            $volume->copyFile($from, $to);
        } else {
            $this->_createTransformForAsset($asset, $index);
        }

        return $volume->fileExists($asset->folderPath . $this->getTransformSubpath($asset, $index));
    }

    /**
     * Normalize a transform from handle or a set of properties to an AssetTransform.
     *
     * @param AssetTransform|string|array|null $transform
     * @return AssetTransform|null
     * @throws AssetTransformException if $transform is an invalid transform handle
     */
    public function normalizeTransform($transform)
    {
        if (!$transform) {
            return null;
        }

        if ($transform instanceof AssetTransform) {
            return $transform;
        }

        if (is_array($transform)) {
            return new AssetTransform($transform);
        }

        if (is_object($transform)) {
            return new AssetTransform(ArrayHelper::toArray($transform, [
                'id',
                'name',
                'handle',
                'width',
                'height',
                'format',
                'dimensionChangeTime',
                'mode',
                'position',
                'quality',
                'interlace',
            ]));
        }

        if (is_string($transform)) {
            if (($transformModel = $this->getTransformByHandle($transform)) === null) {
                throw new AssetTransformException(Craft::t('app', 'Invalid transform handle: {handle}', ['handle' => $transform]));
            }

            return $transformModel;
        }

        return null;
    }

    /**
     * Extend a transform by taking an existing transform and overriding its parameters.
     *
     * @param AssetTransform $transform
     * @param array $parameters
     * @return AssetTransform
     */
    public function extendTransform(AssetTransform $transform, array $parameters): AssetTransform
    {
        if (!empty($parameters)) {
            // Don't change the same transform
            $transform = clone $transform;

            $whiteList = [
                'width',
                'height',
                'format',
                'mode',
                'position',
                'quality',
                'interlace',
            ];

            $nullables = [
                'id',
                'name',
                'handle',
                'uid',
                'dimensionChangeTime',
            ];

            foreach ($parameters as $parameter => $value) {
                if (in_array($parameter, $whiteList, true)) {
                    $transform->{$parameter} = $value;
                }
            }

            foreach ($nullables as $nullable) {
                $transform->{$nullable} = null;
            }
        }

        return $transform;
    }

    /**
     * Store a transform index data by it's model.
     *
     * @param AssetTransformIndex $index
     * @return AssetTransformIndex
     */
    public function storeTransformIndexData(AssetTransformIndex $index): AssetTransformIndex
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
                'error',
                'dateIndexed',
            ], [], false)
        );

        if ($index->id !== null) {
            Db::update(Table::ASSETTRANSFORMINDEX, $values, [
                'id' => $index->id,
            ], [], true, $this->db);
        } else {
            Db::insert(Table::ASSETTRANSFORMINDEX, $values, true, $this->db);
            $index->id = $this->db->getLastInsertID(Table::ASSETTRANSFORMINDEX);
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
     * @return AssetTransformIndex|null
     */
    public function getTransformIndexModelById(int $transformId)
    {
        $result = $this->_createTransformIndexQuery()
            ->where(['id' => $transformId])
            ->one();

        return $result ? new AssetTransformIndex($result) : null;
    }

    /**
     * Get a transform index model by a row id.
     *
     * @param int $assetId
     * @param string $transformHandle
     * @return AssetTransformIndex|null
     */
    public function getTransformIndexModelByAssetIdAndHandle(int $assetId, string $transformHandle)
    {
        $result = $this->_createTransformIndexQuery()
            ->where([
                'assetId' => $assetId,
                'location' => '_' . $transformHandle
            ])
            ->one();

        return $result ? new AssetTransformIndex($result) : null;
    }

    /**
     * Get URL for Transform by TransformIndexId.
     *
     * @param int $transformId
     * @return string
     */
    public function getUrlForTransformByIndexId(int $transformId): string
    {
        $transformIndexModel = $this->getTransformIndexModelById($transformId);

        $asset = Craft::$app->getAssets()->getAssetById($transformIndexModel->assetId);

        return $this->getUrlForTransformByAssetAndTransformIndex($asset, $transformIndexModel);
    }

    /**
     * Get URL for Transform by the transform index model.
     *
     * @param Asset $asset
     * @param AssetTransformIndex $transformIndexModel
     * @return string
     */
    public function getUrlForTransformByAssetAndTransformIndex(Asset $asset, AssetTransformIndex $transformIndexModel): string
    {
        return AssetsHelper::generateUrl($asset->getVolume(), $asset, $this->getTransformUri($asset, $transformIndexModel), $transformIndexModel);
    }

    /**
     * Delete transform records by an Asset id
     *
     * @param int $assetId
     */
    public function deleteTransformIndexDataByAssetId(int $assetId)
    {
        Db::delete(Table::ASSETTRANSFORMINDEX, [
            'assetId' => $assetId,
        ], [], $this->db);
    }

    /**
     * Delete a transform index by.
     *
     * @param int $indexId
     */
    public function deleteTransformIndex(int $indexId)
    {
        Db::delete(Table::ASSETTRANSFORMINDEX, [
            'id' => $indexId,
        ], [], $this->db);
    }

    /**
     * Get a local image source to use for transforms.
     *
     * @param Asset $asset
     * @return string
     * @throws VolumeException If there was an error downloading the remote file.
     * @throws VolumeObjectNotFoundException If the file cannot be found.
     */
    public function getLocalImageSource(Asset $asset): string
    {
        $volume = $asset->getVolume();

        $imageSourcePath = $asset->getImageTransformSourcePath();

        try {
            if (!$volume instanceof LocalVolumeInterface) {
                if (!is_file($imageSourcePath) || filesize($imageSourcePath) === 0) {
                    // Delete it just in case it's a 0-byter
                    FileHelper::unlink($imageSourcePath);

                    $prefix = pathinfo($asset->filename, PATHINFO_FILENAME) . '.delimiter.';
                    $extension = $asset->getExtension();
                    $tempFilename = uniqid($prefix, true) . '.' . $extension;
                    $tempPath = Craft::$app->getPath()->getTempPath();
                    $tempFilePath = $tempPath . DIRECTORY_SEPARATOR . $tempFilename;

                    // Fetch a list of existing temp files for this image.
                    $files = FileHelper::findFiles($tempPath, [
                        'only' => [
                            $prefix . '*' . '.' . $extension
                        ]
                    ]);

                    // And clean them up.
                    if (!empty($files)) {
                        foreach ($files as $filePath) {
                            FileHelper::unlink($filePath);
                        }
                    }
                    $volume->saveFileLocally($asset->getPath(), $tempFilePath);

                    if (!is_file($tempFilePath) || filesize($tempFilePath) === 0) {
                        if (!FileHelper::unlink($tempFilePath)) {
                            Craft::warning("Unable to delete the file \"$tempFilePath\".", __METHOD__);
                        }
                        throw new VolumeException(Craft::t('app', 'Tried to download the source file for image “{file}”, but it was 0 bytes long.',
                            ['file' => $asset->filename]));
                    }

                    $this->storeLocalSource($tempFilePath, $imageSourcePath);

                    // Delete the leftover data.
                    $this->queueSourceForDeletingIfNecessary($imageSourcePath);
                    if (!FileHelper::unlink($tempFilePath)) {
                        Craft::warning("Unable to delete the file \"$tempFilePath\".", __METHOD__);
                    }
                }
            }
        } catch (AssetException $exception) {
            // Make sure we throw a new exception
            $imageSourcePath = false;
        }

        if (!is_file($imageSourcePath)) {
            throw new VolumeObjectNotFoundException("The file \"{$asset->filename}\" does not exist.");
        }

        $asset->setTransformSource($imageSourcePath);

        return $imageSourcePath;
    }

    /**
     * Get the size of max cached cloud images dimension.
     *
     * @return int
     */
    public function getCachedCloudImageSize(): int
    {
        return (int)Craft::$app->getConfig()->getGeneral()->maxCachedCloudImageSize;
    }

    /**
     * Deletes an image local source if required by config.
     *
     * @param string $imageSource
     */
    public function queueSourceForDeletingIfNecessary($imageSource)
    {
        if (!($this->getCachedCloudImageSize() > 0)) {
            $this->_sourcesToBeDeleted[] = $imageSource;

            if (count($this->_sourcesToBeDeleted) === 1) {
                Craft::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'deleteQueuedSourceFiles']);
            }
        }
    }

    /**
     * Delete all image sources queued up for deletion.
     */
    public function deleteQueuedSourceFiles()
    {
        $this->_sourcesToBeDeleted = array_unique($this->_sourcesToBeDeleted);
        foreach ($this->_sourcesToBeDeleted as $source) {
            FileHelper::unlink($source);
        }
    }

    /**
     * Store a local image copy to a destination path.
     *
     * @param string $source
     * @param string $destination
     */
    public function storeLocalSource(string $source, string $destination = '')
    {
        if (!$destination) {
            $source = $destination;
        }

        $maxCachedImageSize = $this->getCachedCloudImageSize();

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

    /**
     * Detect the auto web-safe format for the Asset. Returns null, if the Asset is not an image.
     *
     * @param Asset $asset
     * @return mixed|string
     * @throws AssetLogicException If attempting to detect an image format for a non-image.
     */
    public function detectAutoTransformFormat(Asset $asset)
    {
        if (in_array(mb_strtolower($asset->getExtension()), Image::webSafeFormats(), true)) {
            return $asset->getExtension();
        }

        if ($asset->kind === Asset::KIND_IMAGE) {
            // The only reasonable way to check for transparency is with Imagick. If Imagick is not present, then
            // we fallback to jpg
            $images = Craft::$app->getImages();
            if ($images->getIsGd() || !method_exists(\Imagick::class, 'getImageAlphaChannel')) {
                return 'jpg';
            }

            $volume = $asset->getVolume();

            $tempFilename = uniqid(pathinfo($asset->filename, PATHINFO_FILENAME), true) . '.' . $asset->getExtension();
            $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;
            $volume->saveFileLocally($asset->getPath(), $tempPath);

            $image = $images->loadImage($tempPath);

            if ($image->getIsTransparent()) {
                $format = 'png';
            } else {
                $format = 'jpg';
            }

            if (!$volume instanceof LocalVolumeInterface) {
                // Store for potential later use and queue for deletion if needed.
                $asset->setTransformSource($tempPath);
                $this->queueSourceForDeletingIfNecessary($tempPath);
            } else {
                // For local, though, we just delete the temp file.
                FileHelper::unlink($tempPath);
            }

            return $format;
        }

        throw new AssetLogicException(Craft::t('app',
            'Tried to detect the appropriate image format for a non-image!'));
    }

    /**
     * Return a subfolder used by the Transform Index for the Asset.
     *
     * @param Asset $asset
     * @param AssetTransformIndex $index
     * @return string
     */
    public function getTransformSubfolder(Asset $asset, AssetTransformIndex $index): string
    {
        $path = $index->location;

        if (!empty($index->filename) && $index->filename !== $asset->filename) {
            $path .= DIRECTORY_SEPARATOR . $asset->id;
        }

        return $path;
    }

    /**
     * Return the filename used by the Transform Index for the Asset.
     *
     * @param Asset $asset
     * @param AssetTransformIndex $index
     * @return string
     */
    public function getTransformFilename(Asset $asset, AssetTransformIndex $index): string
    {
        return $index->filename ?: $asset->filename;
    }

    /**
     * Returns the path to a transform, relative to the asset's folder.
     *
     * @param Asset $asset
     * @param AssetTransformIndex $index
     * @return string
     */
    public function getTransformSubpath(Asset $asset, AssetTransformIndex $index): string
    {
        return $this->getTransformSubfolder($asset, $index) . DIRECTORY_SEPARATOR . $this->getTransformFilename($asset, $index);
    }

    /**
     * Returns the URI for a transform, relative to the asset's folder.
     *
     * @param Asset $asset
     * @param AssetTransformIndex $index
     * @return string
     */
    public function getTransformUri(Asset $asset, AssetTransformIndex $index): string
    {
        $uri = $this->getTransformSubpath($asset, $index);

        if (DIRECTORY_SEPARATOR !== '/') {
            $uri = str_replace(DIRECTORY_SEPARATOR, '/', $uri);
        }

        return $uri;
    }

    /**
     * Delete *ALL* transform data (including thumbs and sources) associated with the Asset.
     *
     * @param Asset $asset
     */
    public function deleteAllTransformData(Asset $asset)
    {
        $this->deleteResizedAssetVersion($asset);
        $this->deleteCreatedTransformsForAsset($asset);
        $this->deleteTransformIndexDataByAssetId($asset->id);

        $file = Craft::$app->getPath()->getAssetSourcesPath() . DIRECTORY_SEPARATOR . $asset->id . '.' . pathinfo($asset->filename, PATHINFO_EXTENSION);

        if (!FileHelper::unlink($file)) {
            Craft::warning("Unable to delete the file \"$file\".", __METHOD__);
        }
    }

    /**
     * Delete all the generated thumbnails for the Asset.
     *
     * @param Asset $asset
     */
    public function deleteResizedAssetVersion(Asset $asset)
    {
        $dirs = [
            Craft::$app->getPath()->getAssetThumbsPath(),
            Craft::$app->getPath()->getImageEditorSourcesPath() . '/' . $asset->id
        ];

        foreach ($dirs as $dir) {
            if (file_exists($dir)) {
                $files = glob($dir . '/[0-9]*/' . $asset->id . '.[a-z]*');

                if (!is_array($files)) {
                    Craft::warning('Could not list files in ' . $dir . ' when deleting resized asset versions.');
                    continue;
                }

                foreach ($files as $path) {
                    if (!FileHelper::unlink($path)) {
                        Craft::warning("Unable to delete the asset thumbnail \"$path\".", __METHOD__);
                    }
                }
            }
        }
    }

    /**
     * Delete created transforms for an Asset.
     *
     * @param Asset $asset
     * @throws VolumeException if something went very wrong when deleting a transform
     */
    public function deleteCreatedTransformsForAsset(Asset $asset)
    {
        $transformIndexes = $this->getAllCreatedTransformsForAsset($asset);

        $volume = $asset->getVolume();

        foreach ($transformIndexes as $transformIndex) {
            // Fire a 'beforeDeleteTransforms' event
            if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_TRANSFORMS)) {
                $this->trigger(self::EVENT_BEFORE_DELETE_TRANSFORMS, new AssetTransformImageEvent([
                    'asset' => $asset,
                    'transformIndex' => $transformIndex,
                ]));
            }

            $volume->deleteFile($asset->folderPath . $this->getTransformSubpath($asset, $transformIndex));

            // Fire an 'afterDeleteTransforms' event
            if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_TRANSFORMS)) {
                $this->trigger(self::EVENT_AFTER_DELETE_TRANSFORMS, new AssetTransformImageEvent([
                    'asset' => $asset,
                    'transformIndex' => $transformIndex,
                ]));
            }
        }
    }

    /**
     * Get an array of AssetTransformIndex models for all created transforms for an Asset.
     *
     * @param Asset $asset
     * @return array
     */
    public function getAllCreatedTransformsForAsset(Asset $asset): array
    {
        $results = $this->_createTransformIndexQuery()
            ->where(['assetId' => $asset->id])
            ->all();

        foreach ($results as $key => $result) {
            $results[$key] = new AssetTransformIndex($result);
        }

        return $results;
    }

    /**
     * @return AssetTransformIndex|null
     */
    public function getActiveTransformIndex()
    {
        return $this->_activeTransformIndex;
    }

    /**
     * @param AssetTransformIndex $index
     */
    public function setActiveTransformIndex(AssetTransformIndex $index)
    {
        $this->_activeTransformIndex = $index;
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
                'location',
                'volumeId',
                'fileExists',
                'inProgress',
                'error',
                'dateIndexed',
                'dateUpdated',
                'dateCreated',
            ])
            ->from([Table::ASSETTRANSFORMINDEX]);
    }

    /**
     * Returns a Query object prepped for retrieving transforms.
     *
     * @return Query
     */
    private function _createTransformQuery(): Query
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
                'interlace',
                'dimensionChangeTime',
                'uid'
            ])
            ->from([Table::ASSETTRANSFORMS])
            ->orderBy(['name' => SORT_ASC]);
    }

    /**
     * Returns a transform's folder name.
     *
     * @param AssetTransform $transform
     * @return string
     */
    private function _getTransformFolderName(AssetTransform $transform): string
    {
        if ($transform->getIsNamedTransform()) {
            return $this->_getNamedTransformFolderName($transform);
        }

        return $this->_getUnnamedTransformFolderName($transform);
    }

    /**
     * Returns a named transform's folder name.
     *
     * @param AssetTransform|string $transform
     * @return string
     */
    private function _getNamedTransformFolderName($transform): string
    {
        return '_' . ($transform instanceof AssetTransform ? $transform->handle : $transform);
    }

    /**
     * Returns an unnamed transform's folder name.
     *
     * @param AssetTransform $transform
     * @return string
     */
    private function _getUnnamedTransformFolderName(AssetTransform $transform): string
    {
        return '_' . ($transform->width ?: 'AUTO') . 'x' . ($transform->height ?: 'AUTO') .
            '_' . $transform->mode .
            '_' . $transform->position .
            ($transform->quality ? '_' . $transform->quality : '') .
            '_' . $transform->interlace;
    }

    /**
     * Create a transform for the Asset by the transform index.
     *
     * @param Asset $asset
     * @param AssetTransformIndex $index
     * @throws AssetTransformException If a transform index has an invalid transform assigned.
     */
    private function _createTransformForAsset(Asset $asset, AssetTransformIndex $index)
    {
        if (!Image::canManipulateAsImage(pathinfo($asset->filename, PATHINFO_EXTENSION))) {
            return;
        }

        $transform = $index->getTransform();
        $images = Craft::$app->getImages();

        if ($index->detectedFormat === null) {
            $index->detectedFormat = $index->format ?: $this->detectAutoTransformFormat($asset);
        }

        if ($index->format === 'webp' && !$images->getSupportsWebP()) {
            throw new AssetTransformException("The `webp` format is not supported on this server!");
        }

        $volume = $asset->getVolume();
        $transformPath = $asset->folderPath . $this->getTransformSubpath($asset, $index);

        // Already created. Relax, grasshopper!
        if ($volume->fileExists($transformPath)) {
            $dateModified = $volume->getDateModified($transformPath);
            $dimensionChangeTime = $index->getTransform()->dimensionChangeTime;

            if (!$dimensionChangeTime || $dimensionChangeTime->getTimestamp() <= $dateModified) {
                return;
            }

            // Let's cook up a new one.
            try {
                $volume->deleteFile($transformPath);
            } catch (\Throwable $exception) {
                // Unlikely, but if it got deleted while we were comparing timestamps, don't freak out.
            }
        }

        $imageSource = $asset->getTransformSource();
        $quality = $transform->quality ?: Craft::$app->getConfig()->getGeneral()->defaultImageQuality;

        if (strtolower($asset->getExtension()) === 'svg' && $index->detectedFormat !== 'svg') {
            $image = $images->loadImage($imageSource, true, max($transform->width, $transform->height));
        } else {
            $image = $images->loadImage($imageSource);
        }

        if ($image instanceof Raster) {
            $image->setQuality($quality);
        }

        // Save this for Image to use if needed.
        $this->setActiveTransformIndex($index);

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

        $this->trigger(self::EVENT_GENERATE_TRANSFORM, $event);

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
            $volume->createFileByStream($transformPath, $stream, []);
        } catch (VolumeObjectExistsException $e) {
            // We're fine with that.
        }

        FileHelper::unlink($tempPath);

        $volume = $asset->getVolume();

        if (!$volume instanceof LocalVolumeInterface) {
            $this->queueSourceForDeletingIfNecessary($imageSource);
        }
    }

    /**
     * Gets a transform's record by uid.
     *
     * @param string $uid
     * @return AssetTransformRecord
     */
    private function _getTransformRecord(string $uid): AssetTransformRecord
    {
        return AssetTransformRecord::findOne(['uid' => $uid]) ?? new AssetTransformRecord();
    }
}
