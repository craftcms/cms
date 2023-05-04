<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\imagetransforms\EagerImageTransformerInterface;
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\base\MemoizableArray;
use craft\db\Connection;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\errors\ImageTransformException;
use craft\events\AssetEvent;
use craft\events\ConfigEvent;
use craft\events\ImageTransformEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\ImageTransforms as TransformHelper;
use craft\helpers\StringHelper;
use craft\imagetransforms\ImageTransformer;
use craft\models\ImageTransform;
use craft\records\ImageTransform as ImageTransformRecord;
use DateTime;
use Throwable;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\di\Instance;

/**
 * Image Transforms service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getImageTransforms()|`Craft::$app->imageTransforms`]].
 *
 * @property-read ImageTransform[] $allTransforms
 * @property-read array $pendingTransformIndexIds
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
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
     */
    public const EVENT_BEFORE_APPLY_TRANSFORM_DELETE = 'beforeApplyTransformDelete';

    /**
     * @event AssetTransformEvent The event that is triggered after an asset transform is deleted
     */
    public const EVENT_AFTER_DELETE_IMAGE_TRANSFORM = 'afterDeleteImageTransform';

    /**
     * @event AssetEvent The event that is triggered when a transform is being generated for an Asset.
     */
    public const EVENT_BEFORE_INVALIDATE_ASSET_TRANSFORMS = 'beforeInvalidateAssetTransforms';

    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering image transformers.
     */
    public const EVENT_REGISTER_IMAGE_TRANSFORMERS = 'registerImageTransformers';

    /**
     * @var Connection|array|string The database connection to use
     */
    public string|array|Connection $db = 'db';

    /**
     * @var MemoizableArray<ImageTransform>|null
     * @see _transforms()
     */
    private ?MemoizableArray $_transforms = null;

    /**
     * @var ImageTransformerInterface[]
     */
    private array $_imageTransformers = [];

    /**
     * Serializer
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
                'imageTransform' => $transform,
                'isNew' => $isNewTransform,
            ]));
        }

        if ($runValidation && !$transform->validate()) {
            Craft::info('Asset transform not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewTransform) {
            $transform->uid = StringHelper::UUID();
        } elseif (!$transform->uid) {
            $transform->uid = Db::uidById(Table::IMAGETRANSFORMS, $transform->id, $this->db);
        }

        $projectConfig = Craft::$app->getProjectConfig();
        $configPath = ProjectConfig::PATH_IMAGE_TRANSFORMS . '.' . $transform->uid;
        $projectConfig->set($configPath, $transform->getConfig(), "Saving transform “{$transform->handle}”");

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
            $fillChanged = $transformRecord->fill !== ($data['fill'] ?? null);
            $upscaleChanged = $transformRecord->upscale !== ($data['upscale'] ?? null);

            if ($heightChanged || $modeChanged || $qualityChanged || $interlaceChanged || $fillChanged || $upscaleChanged) {
                $transformRecord->parameterChangeTime = Db::prepareDateForDb(new DateTime());
                $deleteTransformIndexes = true;
            }

            $transformRecord->mode = $data['mode'];
            $transformRecord->position = $data['position'];
            $transformRecord->width = $data['width'];
            $transformRecord->height = $data['height'];
            $transformRecord->quality = $data['quality'];
            $transformRecord->interlace = $data['interlace'];
            $transformRecord->format = $data['format'];
            $transformRecord->fill = $data['fill'] ?? null;
            $transformRecord->upscale = $data['upscale'] ?? true;
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
                'imageTransform' => $this->getTransformById($transformRecord->id),
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
     * @throws Exception on DB error
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
     * @param ImageTransform $transform The transform
     * @return bool Whether the transform was deleted
     */
    public function deleteTransform(ImageTransform $transform): bool
    {
        // Fire a 'beforeDeleteImageTransform' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_IMAGE_TRANSFORM)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_IMAGE_TRANSFORM, new ImageTransformEvent([
                'imageTransform' => $transform,
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
                'imageTransform' => $transform,
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
                'imageTransform' => $transform,
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
        $transformsByTransformer = [];

        /** @var ImageTransform|null $refTransform */
        $refTransform = null;

        foreach ($transforms as $transform) {
            // Is this a srcset-style size (2x, 100w, etc.)?
            try {
                [$sizeValue, $sizeUnit] = AssetsHelper::parseSrcsetSize($transform);
            } catch (InvalidArgumentException) {
                // All good.
                $sizeValue = $sizeUnit = null;
            }

            if (isset($sizeValue, $sizeUnit)) {
                if ($refTransform === null || !$refTransform->width) {
                    throw new InvalidArgumentException("Can’t eager-load transform “{$transform}” without a prior transform that specifies the base width");
                }

                $transform = new ImageTransform($refTransform->toArray([
                    'format',
                    'interlace',
                    'mode',
                    'position',
                    'quality',
                ]));

                if ($sizeUnit === 'w') {
                    $transform->width = (int)$sizeValue;
                } else {
                    $transform->width = (int)ceil($refTransform->width * $sizeValue);
                }

                // Only set the height if the reference transform has a height set on it
                if ($refTransform->height) {
                    if ($sizeUnit === 'w') {
                        $transform->height = (int)ceil($refTransform->height * $transform->width / $refTransform->width);
                    } else {
                        $transform->height = (int)ceil($refTransform->height * $sizeValue);
                    }
                }
            }

            $transform = TransformHelper::normalizeTransform($transform);
            $transformsByTransformer[$transform->getTransformer()][] = $transform;

            if (!isset($sizeValue)) {
                // Use this as the reference transform in case any srcset-style transforms follow it
                $refTransform = $transform;
            }
        }

        foreach ($transformsByTransformer as $type => $typeTransforms) {
            $transformer = $this->getImageTransformer($type);
            if ($transformer instanceof EagerImageTransformerInterface) {
                $transformer->eagerLoadTransforms($typeTransforms, $assets);
            }
        }
    }

    /**
     * @template T of ImageTransformerInterface
     * @param string $type
     * @phpstan-param class-string<T> $type
     * @param array $config
     * @return T
     * @throws InvalidConfigException
     */
    public function getImageTransformer(string $type, array $config = []): ImageTransformerInterface
    {
        if (!array_key_exists($type, $this->_imageTransformers)) {
            if (!is_subclass_of($type, ImageTransformerInterface::class)) {
                throw new ImageTransformException("Invalid image transformer: $type");
            }

            $this->_imageTransformers[$type] = Craft::createObject(array_merge(['class' => $type], $config));
        }

        return $this->_imageTransformers[$type];
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
                'asset' => $asset,
            ]));
        }

        $transformers = $this->getAllImageTransformers();

        foreach ($transformers as $type) {
            $transformer = $this->getImageTransformer($type);
            $transformer->invalidateAssetTransforms($asset);
        }
    }

    /**
     * Return all available image transformers.
     *
     * @return string[]
     * @phpstan-return class-string<ImageTransformerInterface>[]
     */
    public function getAllImageTransformers(): array
    {
        $transformers = [
            ImageTransformer::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $transformers,
        ]);

        $this->trigger(self::EVENT_REGISTER_IMAGE_TRANSFORMERS, $event);

        return $event->types;
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
                'fill',
                'upscale',
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
