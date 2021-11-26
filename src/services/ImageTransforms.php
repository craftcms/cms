<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ImageTransformDriverInterface;
use craft\base\MemoizableArray;
use craft\db\Connection;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\errors\ImageTransformException;
use craft\events\ImageTransformEvent;
use craft\events\TransformImageEvent;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\ImageTransforms as TransformHelper;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\models\ImageTransform;
use craft\models\ImageTransformIndex;
use craft\records\ImageTransform as ImageTransformRecord;
use DateTime;
use Throwable;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\di\Instance;

/**
 * Image Transforms service.
 * An instance of the Image Transforms service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getImageTransforms()|`Craft::$app->imageTransforms`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 *
 * @property-read ImageTransform[] $allTransforms
 * @property-read array $pendingTransformIndexIds
 */
class ImageTransforms extends Component
{
    /**
     * @event AssetTransformEvent The event that is triggered before an asset transform is saved
     */
    public const EVENT_BEFORE_SAVE_IMAGE_TRANSFORM = 'beforeSaveImageTransform';

    /**
     * @event AssetTransformEvent The event that is triggered after an asset transform is saved
     */
    public const EVENT_AFTER_SAVE_IMAGE_TRANSFORM = 'afterSaveImageTransform';

    /**
     * @event AssetTransformEvent The event that is triggered before an asset transform is deleted
     */
    public const EVENT_BEFORE_DELETE_IMAGE_TRANSFORM = 'beforeDeleteImageTransform';

    /**
     * @event AssetTransformEvent The event that is triggered after an asset transform is deleted
     */
    public const EVENT_AFTER_DELETE_IMAGE_TRANSFORM = 'afterDeleteImageTransform';

    /**
     * @event GenerateTransformEvent The event that is triggered when a transform is being generated for an Asset.
     */
    public const EVENT_GENERATE_TRANSFORM = 'generateTransform';

    /**
     * @event AssetTransformImageEvent The event that is triggered before deleting generated transforms.
     */
    public const EVENT_BEFORE_DELETE_TRANSFORMS = 'beforeDeleteTransforms';

    /**
     * @event AssetTransformEvent The event that is triggered before a transform delete is applied to the database.
     * @since 3.1.0
     */
    public const EVENT_BEFORE_APPLY_TRANSFORM_DELETE = 'beforeApplyTransformDelete';

    /**
     * @event AssetTransformImageEvent The event that is triggered after deleting generated transforms.
     */
    public const EVENT_AFTER_DELETE_TRANSFORMS = 'afterDeleteTransforms';

    /**
     * @var Connection|array|string The database connection to use
     * @since 3.5.4
     */
    public $db = 'db';

    /**
     * @var MemoizableArray<ImageTransform>|null
     * @see _transforms()
     */
    private ?MemoizableArray $_transforms = null;

    /**
     * @var array|null
     */
    private ?array $_eagerLoadedTransformIndexes = null;

    /**
     * @var ImageTransformIndex|null
     */
    private ?ImageTransformIndex $_activeTransformIndex = null;

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
    public function init(): void
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::class);
    }

    /**
     * Returns a memoizable array of all named asset transforms.
     *
     * @return MemoizableArray<ImageTransform>
     */
    private function _transforms(): MemoizableArray
    {
        if (!isset($this->_transforms)) {
            $transforms = [];
            foreach ($this->_createTransformQuery()->all() as $result) {
                $transforms[] = new ImageTransform($result);
            }
            $this->_transforms = new MemoizableArray($transforms);
        }

        return $this->_transforms;
    }

    /**
     * Returns all named asset transforms.
     *
     * @return ImageTransform[]
     */
    public function getAllTransforms(): array
    {
        return $this->_transforms()->all();
    }

    /**
     * Returns an asset transform by its handle.
     *
     * @param string $handle
     * @return ImageTransform|null
     */
    public function getTransformByHandle(string $handle): ?ImageTransform
    {
        return $this->_transforms()->firstWhere('handle', $handle, true);
    }

    /**
     * Returns an asset transform by its ID.
     *
     * @param int $id
     * @return ImageTransform|null
     */
    public function getTransformById(int $id): ?ImageTransform
    {
        return $this->_transforms()->firstWhere('id', $id);
    }

    /**
     * Returns an asset transform by its UID.
     *
     * @param string $uid
     * @return ImageTransform|null
     * @since 3.1.0
     */
    public function getTransformByUid(string $uid): ?ImageTransform
    {
        return $this->_transforms()->firstWhere('uid', $uid, true);
    }

    /**
     * Saves an asset transform.
     *
     * @param ImageTransform $transform The transform to be saved
     * @param bool $runValidation Whether the transform should be validated
     * @return bool
     * @throws ImageTransformException If attempting to update a non-existing transform.
     */
    public function saveTransform(ImageTransform $transform, bool $runValidation = true): bool
    {
        $isNewTransform = !$transform->id;

        // Fire a 'beforeSaveImageTransform' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_IMAGE_TRANSFORM)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_IMAGE_TRANSFORM, new ImageTransformEvent([
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
            $transform->uid = Db::uidById(Table::IMAGETRANSFORMS, $transform->id, $this->db);
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

        $configPath = ProjectConfig::PATH_IMAGE_TRANSFORMS . '.' . $transform->uid;
        $projectConfig->set($configPath, $configData, "Saving transform “{$transform->handle}”");

        if ($isNewTransform) {
            $transform->id = Db::idByUid(Table::IMAGETRANSFORMS, $transform->uid, $this->db);
        }

        return true;
    }

    /**
     * Handle transform change.
     *
     * @param ConfigEvent $event
     */
    public function handleChangedTransform(ConfigEvent $event): void
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
                $transformRecord->parameterChangeTime = new DateTime('@' . time());
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
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        if ($deleteTransformIndexes) {
            Db::delete(Table::IMAGETRANSFORMINDEX, [
                'transformString' => '_' . $transformRecord->handle,
            ], [], $this->db);
        }

        // Clear caches
        $this->_transforms = null;

        // Fire an 'afterSaveImageTransform' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_IMAGE_TRANSFORM)) {
            $this->trigger(self::EVENT_AFTER_SAVE_IMAGE_TRANSFORM, new ImageTransformEvent([
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
     * @param int|ImageTransform $transform The transform
     * @return bool Whether the transform was deleted
     * @throws \yii\db\Exception on DB error
     */
    public function deleteTransform(ImageTransform $transform): bool
    {
        // Fire a 'beforeDeleteImageTransform' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_IMAGE_TRANSFORM)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_IMAGE_TRANSFORM, new ImageTransformEvent([
                'assetTransform' => $transform,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(ProjectConfig::PATH_IMAGE_TRANSFORMS . '.' . $transform->uid, "Delete transform “{$transform->handle}”");
        return true;
    }

    /**
     * Handle transform being deleted
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedTransform(ConfigEvent $event): void
    {
        $transformUid = $event->tokenMatches[0];

        $transform = $this->getTransformByUid($transformUid);

        if (!$transform) {
            return;
        }

        // Fire a 'beforeApplyTransformDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_TRANSFORM_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_TRANSFORM_DELETE, new ImageTransformEvent([
                'assetTransform' => $transform,
            ]));
        }

        Db::delete(Table::IMAGETRANSFORMS, [
            'uid' => $transformUid,
        ], [], $this->db);

        // Clear caches
        $this->_transforms = null;

        // Fire an 'afterDeleteImageTransform' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_IMAGE_TRANSFORM)) {
            $this->trigger(self::EVENT_AFTER_DELETE_IMAGE_TRANSFORM, new ImageTransformEvent([
                'assetTransform' => $transform,
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
    public function eagerLoadTransforms(array $assets, array $transforms): void
    {
        if (empty($assets) || empty($transforms)) {
            return;
        }

        // Index the assets by ID
        $assetsById = ArrayHelper::index($assets, 'id');

        // Get the index conditions
        $transformsByFingerprint = [];
        $indexCondition = ['or'];

        /** @var ImageTransform|null $refTransform */
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

            $transformString = $fingerprint = TransformHelper::getTransformString($transform);
            $transformCondition = ['and', ['transformString' => $transformString]];

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
                $this->_eagerLoadedTransformIndexes[$indexFingerprint] = $result;
            } else {
                $invalidIndexIds[] = $result['id'];
            }
        }

        // Delete any invalid indexes
        if (!empty($invalidIndexIds)) {
            Db::delete(Table::IMAGETRANSFORMINDEX, [
                'id' => $invalidIndexIds,
            ], [], $this->db);
        }
    }

    /**
     * Get a transform index row. If it doesn't exist - create one.
     *
     * @param Asset $asset
     * @param ImageTransform|string|array|null $transform
     * @return ImageTransformIndex
     * @throws ImageTransformException if the transform cannot be found by the handle
     */
    public function getTransformIndex(Asset $asset, $transform): ImageTransformIndex
    {
        $transform = $this->normalizeTransform($transform);

        if ($transform === null) {
            throw new ImageTransformException('There was a problem finding the transform.');
        }

        $transformString = TransformHelper::getTransformString($transform);

        // Was it eager-loaded?
        $fingerprint = $asset->id . ':' . $transformString . ($transform->format === null ? '' : ':' . $transform->format);

        if (isset($this->_eagerLoadedTransformIndexes[$fingerprint])) {
            $result = $this->_eagerLoadedTransformIndexes[$fingerprint];
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
            $transformIndex = new ImageTransformIndex($result);

            if ($this->validateTransformIndexResult($result, $transform, $asset)) {
                return $transformIndex;
            }

            // Delete the out-of-date record
            Db::delete(Table::IMAGETRANSFORMINDEX, [
                'id' => $result['id'],
            ], [], $this->db);

            // And the generated transform itself, too
            $transform->getImageTransformer()->invalidateTransform($asset, $transformIndex);
        } else {
            // Create a new record
            $transformIndex = new ImageTransformIndex([
                'assetId' => $asset->id,
                'format' => $transform->format,
                'driver' => $transform->getDriver(),
                'dateIndexed' => Db::prepareDateForDb(new DateTime()),
                'transformString' => $transformString,
                'fileExists' => false,
                'inProgress' => false,
            ]);
        }

        return $this->storeTransformIndexData($transformIndex);
    }

    /**
     * Validates a transform index result to see if the index is still valid for a given asset.
     *
     * @param array $result
     * @param ImageTransform $transform
     * @param Asset|array $asset The asset object or a raw database result
     * @return bool Whether the index result is still valid
     */
    public function validateTransformIndexResult(array $result, ImageTransform $transform, $asset): bool
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
        if ($result['dateIndexed'] < Db::prepareDateForDb($transform->parameterChangeTime)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $driver
     * @param array $config
     * @return ImageTransformDriverInterface
     * @throws InvalidConfigException
     * @since 4.0.0
     */
    public function getImageTransformer(string $driver, array $config = []): ImageTransformDriverInterface
    {
        // TODO events!
        if (!is_subclass_of($driver, ImageTransformDriverInterface::class)) {
            throw new ImageTransformException($driver . ' is not a valid image transform driver');
        }

        return Craft::createObject(array_merge(['class' => $driver], $config));
    }

    /**
     * Normalize a transform from handle or a set of properties to an ImageTransform.
     *
     * @param ImageTransform|string|array|null $transform
     * @return ImageTransform|null
     * @throws ImageTransformException if $transform is an invalid transform handle
     */
    public function normalizeTransform($transform): ?ImageTransform
    {
        if (!$transform) {
            return null;
        }

        if ($transform instanceof ImageTransform) {
            return $transform;
        }

        if (is_array($transform)) {
            if (array_key_exists('transform', $transform)) {
                $baseTransform = $this->normalizeTransform(ArrayHelper::remove($transform, 'transform'));
                return $this->extendTransform($baseTransform, $transform);
            }

            return new ImageTransform($transform);
        }

        if (is_object($transform)) {
            return new ImageTransform(ArrayHelper::toArray($transform, [
                'id',
                'name',
                'driver',
                'handle',
                'width',
                'height',
                'format',
                'parameterChangeTime',
                'mode',
                'position',
                'quality',
                'interlace',
            ]));
        }

        if (is_string($transform)) {
            if (preg_match(TransformHelper::TRANSFORM_STRING_PATTERN, $transform)) {
                return TransformHelper::createTransformFromString($transform);
            }

            $transform = StringHelper::removeLeft($transform, '_');
            if (($transformModel = $this->getTransformByHandle($transform)) === null) {
                throw new ImageTransformException(Craft::t('app', 'Invalid transform handle: {handle}', ['handle' => $transform]));
            }

            return $transformModel;
        }

        return null;
    }

    /**
     * Extend a transform by taking an existing transform and overriding its parameters.
     *
     * @param ImageTransform $transform
     * @param array $parameters
     * @return ImageTransform
     */
    public function extendTransform(ImageTransform $transform, array $parameters): ImageTransform
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
                'driver'
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
     * @param ImageTransformIndex $index
     * @return ImageTransformIndex
     */
    public function storeTransformIndexData(ImageTransformIndex $index): ImageTransformIndex
    {
        $values = Db::prepareValuesForDb(
            $index->toArray([
                'assetId',
                'driver',
                'filename',
                'format',
                'driver',
                'transformString',
                'volumeId',
                'fileExists',
                'inProgress',
                'error',
                'dateIndexed',
            ], [], false)
        );

        if ($index->id !== null) {
            Db::update(Table::IMAGETRANSFORMINDEX, $values, [
                'id' => $index->id,
            ], [], true, $this->db);
        } else {
            Db::insert(Table::IMAGETRANSFORMINDEX, $values, true, $this->db);
            $index->id = (int)$this->db->getLastInsertID(Table::IMAGETRANSFORMINDEX);
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
    public function deleteTransformIndexDataByAssetId(int $assetId): void
    {
        Db::delete(Table::IMAGETRANSFORMINDEX, [
            'assetId' => $assetId,
        ], [], $this->db);
    }

    /**
     * Delete transform records by Asset ids
     *
     * @param int[] $assetIds
     * @since 4.0.0
     */
    public function deleteTransformIndexDataByAssetIds(array $assetIds): void
    {
        Db::delete(Table::IMAGETRANSFORMINDEX, [
            'assetId' => $assetIds,
        ], [], $this->db);
    }

    /**
     * Delete a transform index by.
     *
     * @param int $indexId
     */
    public function deleteTransformIndex(int $indexId): void
    {
        Db::delete(Table::IMAGETRANSFORMINDEX, [
            'id' => $indexId,
        ], [], $this->db);
    }

    /**
     * Delete *ALL* transform data (including thumbs and sources) associated with the Asset.
     *
     * @param Asset $asset
     */
    public function deleteAllTransformData(Asset $asset): void
    {
        $this->deleteResizedAssetVersion($asset);
        $this->deleteCreatedTransformsForAsset($asset);
        $this->deleteTransformIndexDataByAssetId($asset->id);

        $file = Craft::$app->getPath()->getAssetSourcesPath() . DIRECTORY_SEPARATOR . $asset->id . '.' . pathinfo($asset->getFilename(), PATHINFO_EXTENSION);

        if (file_exists($file)) {
            FileHelper::unlink($file);
        }
    }

    /**
     * Delete all the generated thumbnails for the Asset.
     *
     * @param Asset $asset
     */
    public function deleteResizedAssetVersion(Asset $asset): void
    {
        $dirs = [
            Craft::$app->getPath()->getAssetThumbsPath(),
            Craft::$app->getPath()->getImageEditorSourcesPath() . '/' . $asset->id,
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
     */
    public function deleteCreatedTransformsForAsset(Asset $asset): void
    {
        $transformIndexes = $this->getAllCreatedTransformsForAsset($asset);

        foreach ($transformIndexes as $transformIndex) {
            // Fire a 'beforeDeleteTransforms' event
            if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_TRANSFORMS)) {
                $this->trigger(self::EVENT_BEFORE_DELETE_TRANSFORMS, new TransformImageEvent([
                    'asset' => $asset,
                    'transformIndex' => $transformIndex,
                ]));
            }

            $transformer = $transformIndex->getImageTransformer();
            $transformer->invalidateTransform($asset, $transformIndex);

            // Fire an 'afterDeleteTransforms' event
            if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_TRANSFORMS)) {
                $this->trigger(self::EVENT_AFTER_DELETE_TRANSFORMS, new TransformImageEvent([
                    'asset' => $asset,
                    'transformIndex' => $transformIndex,
                ]));
            }
        }
    }

    /**
     * Get an array of ImageTransformIndex models for all created transforms for an Asset.
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
     * @since 4.0.0
     */
    public function getSimilarTransformIndex(Asset $asset, ImageTransformIndex $index): ?ImageTransformIndex
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

        return $result ? Craft::createObject(ImageTransformIndex::class, $result) : null;
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
                'parameterChangeTime',
                'uid',
            ])
            ->from([Table::IMAGETRANSFORMS])
            ->orderBy(['name' => SORT_ASC]);
    }

    /**
     * Gets a transform's record by uid.
     *
     * @param string $uid
     * @return ImageTransformRecord
     */
    private function _getTransformRecord(string $uid): ImageTransformRecord
    {
        return ImageTransformRecord::findOne(['uid' => $uid]) ?? new ImageTransformRecord();
    }
}
