<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\AssetEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterImageTransformDriversEvent;
use craft\image\transforms\DefaultTransformer;
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
use craft\image\transforms\EagerLoadTransformerInterface;
use craft\image\transforms\TransformerInterface;
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
     * @event AssetTransformEvent The event that is triggered before a transform delete is applied to the database.
     * @since 3.1.0
     */
    public const EVENT_BEFORE_APPLY_TRANSFORM_DELETE = 'beforeApplyTransformDelete';
    
    /**
     * @event AssetTransformEvent The event that is triggered after an asset transform is deleted
     */
    public const EVENT_AFTER_DELETE_IMAGE_TRANSFORM = 'afterDeleteImageTransform';

    /**
     * @event GenerateTransformEvent The event that is triggered when a transform is being generated for an Asset.
     */
    public const EVENT_GENERATE_TRANSFORM = 'generateTransform';

    /**
     * @event AssetEvent The event that is triggered when a transform is being generated for an Asset.
     */
    public const EVENT_BEFORE_INVALIDATE_ASSET_TRANSFORMS = 'beforeInvalidateAssetTransforms';

    /**
     * @event RegisterImageTransformDriversEvent The event that is triggered when registering image transform drivers.
     */
    public const EVENT_REGISTER_IMAGE_TRANSFORM_DRIVERS = 'registerImageTransformDrivers';

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

        // Get the index conditions
        $transformsByDriver = [];

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

            $transform = TransformHelper::normalizeTransform($transform);
            $transformsByDriver[$transform->getDriver()][] = $transform;

            if (!isset($sizeValue)) {
                // Use this as the reference transform in case any srcset-style transforms follow it
                $refTransform = $transform;
            }
        }

        foreach ($transformsByDriver as $driver => $driverTransforms) {
            $driver = $this->getImageTransformer($driver);
            if ($driver instanceof EagerLoadTransformerInterface) {
                $driver->eagerLoadTransforms($assets, $driverTransforms);
            }
        }
    }

    /**
     * @param string $driver
     * @param array $config
     * @return TransformerInterface
     * @throws InvalidConfigException
     * @since 4.0.0
     */
    public function getImageTransformer(string $driver, array $config = []): TransformerInterface
    {
        if (!is_subclass_of($driver, TransformerInterface::class)) {
            throw new ImageTransformException($driver . ' is not a valid image transform driver');
        }

        return Craft::createObject(array_merge(['class' => $driver], $config));
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
        // Fire a 'beforeInvalidateAssetTransforms' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_INVALIDATE_ASSET_TRANSFORMS)) {
            $this->trigger(self::EVENT_BEFORE_INVALIDATE_ASSET_TRANSFORMS, new AssetEvent([
                'asset' => $asset
            ]));
        }

        $drivers = $this->getAllImageTransformerDrivers();

        foreach ($drivers as $driver) {
            call_user_func([$driver, 'invalidateAssetTransforms'], $asset);
        }
    }

    /**
     * Return a list of all image transform drivers.
     *
     * @return array
     */
    public function getAllImageTransformerDrivers(): array
    {
        $drivers = [
            DefaultTransformer::class
        ];

        $event = new RegisterImageTransformDriversEvent([
            'drivers' => $drivers,
        ]);

        $this->trigger(self::EVENT_REGISTER_IMAGE_TRANSFORM_DRIVERS, $event);

        return $event->drivers;
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
