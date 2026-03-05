<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\events\AssetEvent;
use craft\events\ImageTransformEvent;
use craft\events\RegisterComponentTypesEvent;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform as ImageTransformData;
use CraftCms\Cms\Image\Events\ApplyingTransformDelete;
use CraftCms\Cms\Image\Events\DeletingTransform;
use CraftCms\Cms\Image\Events\InvalidatingAssetTransforms;
use CraftCms\Cms\Image\Events\RegisterImageTransformers;
use CraftCms\Cms\Image\Events\SavingTransform;
use CraftCms\Cms\Image\Events\TransformDeleted;
use CraftCms\Cms\Image\Events\TransformSaved;
use CraftCms\Cms\Image\ImageTransforms as ImageTransformsService;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use Illuminate\Support\Facades\Event as EventFacade;
use yii\base\Component;

/**
 * Image Transforms service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getImageTransforms()|`Craft::$app->getImageTransforms()`]].
 *
 * @property-read ImageTransformData[] $allTransforms
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see ImageTransformsService} instead.
 */
class ImageTransforms extends Component
{
    /**
     * @event ImageTransformEvent The event that is triggered before an image transform is saved
     */
    public const EVENT_BEFORE_SAVE_IMAGE_TRANSFORM = 'beforeSaveImageTransform';

    /**
     * @event ImageTransformEvent The event that is triggered after an image transform is saved
     */
    public const EVENT_AFTER_SAVE_IMAGE_TRANSFORM = 'afterSaveImageTransform';

    /**
     * @event ImageTransformEvent The event that is triggered before an image transform is deleted
     */
    public const EVENT_BEFORE_DELETE_IMAGE_TRANSFORM = 'beforeDeleteImageTransform';

    /**
     * @event ImageTransformEvent The event that is triggered before a transform delete is applied to the database.
     */
    public const EVENT_BEFORE_APPLY_TRANSFORM_DELETE = 'beforeApplyTransformDelete';

    /**
     * @event ImageTransformEvent The event that is triggered after an image transform is deleted
     */
    public const EVENT_AFTER_DELETE_IMAGE_TRANSFORM = 'afterDeleteImageTransform';

    /**
     * @event AssetEvent The event that is triggered before a transform is deleted for an Asset.
     */
    public const EVENT_BEFORE_INVALIDATE_ASSET_TRANSFORMS = 'beforeInvalidateAssetTransforms';

    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering image transformers.
     */
    public const EVENT_REGISTER_IMAGE_TRANSFORMERS = 'registerImageTransformers';

    /**
     * Serializer
     */
    public function __serialize(): array
    {
        return get_object_vars($this);
    }

    /**
     * Returns all named asset transforms.
     *
     * @return ImageTransformData[]
     */
    public function getAllTransforms(): array
    {
        return $this->service()->getAllTransforms()->all();
    }

    /**
     * Returns an asset transform by its handle.
     *
     * @param string $handle
     * @return ImageTransformData|null
     */
    public function getTransformByHandle(string $handle): ?ImageTransformData
    {
        return $this->service()->getTransformByHandle($handle);
    }

    /**
     * Returns an asset transform by its ID.
     *
     * @param int $id
     * @return ImageTransformData|null
     */
    public function getTransformById(int $id): ?ImageTransformData
    {
        return $this->service()->getTransformById($id);
    }

    /**
     * Returns an asset transform by its UID.
     *
     * @param string $uid
     * @return ImageTransformData|null
     */
    public function getTransformByUid(string $uid): ?ImageTransformData
    {
        return $this->service()->getTransformByUid($uid);
    }

    /**
     * Saves an asset transform.
     *
     * @param ImageTransformData $transform The transform to be saved
     * @param bool $runValidation Whether the transform should be validated
     * @return bool
     */
    public function saveTransform(ImageTransformData $transform, bool $runValidation = true): bool
    {
        return $this->service()->saveTransform($transform, $runValidation);
    }

    /**
     * Handle transform change.
     *
     * @param ConfigEvent $event
     */
    public function handleChangedTransform(ConfigEvent $event): void
    {
        $this->service()->handleChangedTransform($event);
    }

    /**
     * Deletes an asset transform by its ID.
     *
     * @param int $transformId The transform's ID
     * @return bool Whether the transform was deleted.
     */
    public function deleteTransformById(int $transformId): bool
    {
        return $this->service()->deleteTransformById($transformId);
    }

    /**
     * Deletes an asset transform.
     *
     * @param ImageTransformData $transform The transform
     * @return bool Whether the transform was deleted
     */
    public function deleteTransform(ImageTransformData $transform): bool
    {
        return $this->service()->deleteTransform($transform);
    }

    /**
     * Handle transform being deleted.
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedTransform(ConfigEvent $event): void
    {
        $this->service()->handleDeletedTransform($event);
    }

    /**
     * Eager-loads transform indexes the given list of assets.
     *
     * @param array $assets The assets or asset data to eager-load transforms for
     * @param array $transforms The transform definitions to eager-load
     */
    public function eagerLoadTransforms(array $assets, array $transforms): void
    {
        $this->service()->eagerLoadTransforms($assets, $transforms);
    }

    /**
     * @template T of ImageTransformerInterface
     * @param class-string<T> $type
     * @param array $config
     * @return T
     */
    public function getImageTransformer(string $type, array $config = []): ImageTransformerInterface
    {
        return $this->service()->getImageTransformer($type, $config);
    }

    /**
     * Delete *ALL* transform data (including thumbs and sources) associated with the Asset.
     *
     * @param Asset $asset
     */
    public function deleteAllTransformData(Asset $asset): void
    {
        $this->service()->deleteAllTransformData($asset);
    }

    /**
     * Delete all the generated thumbnails for the Asset.
     *
     * @param Asset $asset
     */
    public function deleteResizedAssetVersion(Asset $asset): void
    {
        $this->service()->deleteResizedAssetVersion($asset);
    }

    /**
     * Delete created transforms for an Asset.
     *
     * @param Asset $asset
     */
    public function deleteCreatedTransformsForAsset(Asset $asset): void
    {
        $this->service()->deleteCreatedTransformsForAsset($asset);
    }

    /**
     * Return all available image transformers.
     *
     * @return string[]
     * @phpstan-return class-string<ImageTransformerInterface>[]
     */
    public function getAllImageTransformers(): array
    {
        return $this->service()->getAllImageTransformers();
    }

    public static function registerEvents(): void
    {
        EventFacade::listen(SavingTransform::class, function(SavingTransform $event) {
            if (!Craft::$app->getImageTransforms()->hasEventHandlers(self::EVENT_BEFORE_SAVE_IMAGE_TRANSFORM)) {
                return;
            }

            Craft::$app->getImageTransforms()->trigger(self::EVENT_BEFORE_SAVE_IMAGE_TRANSFORM, new ImageTransformEvent([
                'imageTransform' => $event->transform,
                'isNew' => $event->isNew,
            ]));
        });

        EventFacade::listen(TransformSaved::class, function(TransformSaved $event) {
            if (!Craft::$app->getImageTransforms()->hasEventHandlers(self::EVENT_AFTER_SAVE_IMAGE_TRANSFORM)) {
                return;
            }

            Craft::$app->getImageTransforms()->trigger(self::EVENT_AFTER_SAVE_IMAGE_TRANSFORM, new ImageTransformEvent([
                'imageTransform' => $event->transform,
                'isNew' => $event->isNew,
            ]));
        });

        EventFacade::listen(DeletingTransform::class, function(DeletingTransform $event) {
            if (!Craft::$app->getImageTransforms()->hasEventHandlers(self::EVENT_BEFORE_DELETE_IMAGE_TRANSFORM)) {
                return;
            }

            Craft::$app->getImageTransforms()->trigger(self::EVENT_BEFORE_DELETE_IMAGE_TRANSFORM, new ImageTransformEvent([
                'imageTransform' => $event->transform,
            ]));
        });

        EventFacade::listen(ApplyingTransformDelete::class, function(ApplyingTransformDelete $event) {
            if (!Craft::$app->getImageTransforms()->hasEventHandlers(self::EVENT_BEFORE_APPLY_TRANSFORM_DELETE)) {
                return;
            }

            Craft::$app->getImageTransforms()->trigger(self::EVENT_BEFORE_APPLY_TRANSFORM_DELETE, new ImageTransformEvent([
                'imageTransform' => $event->transform,
            ]));
        });

        EventFacade::listen(TransformDeleted::class, function(TransformDeleted $event) {
            if (!Craft::$app->getImageTransforms()->hasEventHandlers(self::EVENT_AFTER_DELETE_IMAGE_TRANSFORM)) {
                return;
            }

            Craft::$app->getImageTransforms()->trigger(self::EVENT_AFTER_DELETE_IMAGE_TRANSFORM, new ImageTransformEvent([
                'imageTransform' => $event->transform,
            ]));
        });

        EventFacade::listen(InvalidatingAssetTransforms::class, function(InvalidatingAssetTransforms $event) {
            if (!Craft::$app->getImageTransforms()->hasEventHandlers(self::EVENT_BEFORE_INVALIDATE_ASSET_TRANSFORMS)) {
                return;
            }

            Craft::$app->getImageTransforms()->trigger(self::EVENT_BEFORE_INVALIDATE_ASSET_TRANSFORMS, new AssetEvent([
                'asset' => $event->asset,
            ]));
        });

        EventFacade::listen(RegisterImageTransformers::class, function(RegisterImageTransformers $event) {
            if (!Craft::$app->getImageTransforms()->hasEventHandlers(self::EVENT_REGISTER_IMAGE_TRANSFORMERS)) {
                return;
            }

            $legacyEvent = new RegisterComponentTypesEvent(['types' => $event->types]);
            Craft::$app->getImageTransforms()->trigger(self::EVENT_REGISTER_IMAGE_TRANSFORMERS, $legacyEvent);
            $event->types = $legacyEvent->types;
        });
    }

    private function service(): ImageTransformsService
    {
        return app(ImageTransformsService::class);
    }
}
