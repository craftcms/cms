<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\events\AssetEvent;
use craft\events\ImageTransformEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\models\ImageTransform as LegacyImageTransform;
use CraftCms\Cms\Asset\AssetTransforms;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Image\Data\ImageTransform as ImageTransformData;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\Events\TransformDeleted;
use CraftCms\Cms\Image\Events\TransformDeleting;
use CraftCms\Cms\Image\Events\TransformDeletionApplying;
use CraftCms\Cms\Image\Events\TransformSaved;
use CraftCms\Cms\Image\Events\TransformSaving;
use CraftCms\Cms\Image\ImageTransforms as ImageTransformsService;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use CraftCms\Yii2Adapter\Asset\ImageTransformers;
use CraftCms\Yii2Adapter\Asset\LegacyImageTransformerDriver;
use CraftCms\Yii2Adapter\Event\TypeRegistryCompatibility;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Log;
use yii\base\Component;

/**
 * Image Transforms service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getImageTransforms()|`Craft::$app->getImageTransforms()`]].
 *
 * @property-read LegacyImageTransform[] $allTransforms
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see ImageTransformsService} instead.
 */
class ImageTransforms extends Component
{
    /** @var array<class-string<ImageTransformerInterface>, ImageTransformerInterface> */
    private array $imageTransformers = [];

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
     * @return LegacyImageTransform[]
     */
    public function getAllTransforms(): array
    {
        return $this->service()->getAllTransforms()
            ->map($this->toLegacyTransform(...))
            ->all();
    }

    /**
     * Returns an asset transform by its handle.
     */
    public function getTransformByHandle(string $handle): ?LegacyImageTransform
    {
        return $this->toLegacyTransform($this->service()->getTransformByHandle($handle));
    }

    /**
     * Returns an asset transform by its ID.
     */
    public function getTransformById(int $id): ?LegacyImageTransform
    {
        return $this->toLegacyTransform($this->service()->getTransformById($id));
    }

    /**
     * Returns an asset transform by its UID.
     */
    public function getTransformByUid(string $uid): ?LegacyImageTransform
    {
        return $this->toLegacyTransform($this->service()->getTransformByUid($uid));
    }

    /**
     * Saves an asset transform.
     *
     * @param  ImageTransformData  $transform  The transform to be saved
     * @param  bool  $runValidation  Whether the transform should be validated
     */
    public function saveTransform(ImageTransformData $transform, bool $runValidation = true): bool
    {
        return $this->service()->saveTransform($transform, $runValidation);
    }

    /**
     * Handle transform change.
     */
    public function handleChangedTransform(ConfigEvent $event): void
    {
        $this->service()->handleChangedTransform($event);
    }

    /**
     * Deletes an asset transform by its ID.
     *
     * @param  int  $transformId  The transform's ID
     * @return bool Whether the transform was deleted.
     */
    public function deleteTransformById(int $transformId): bool
    {
        return $this->service()->deleteTransformById($transformId);
    }

    /**
     * Deletes an asset transform.
     *
     * @param  ImageTransformData  $transform  The transform
     * @return bool Whether the transform was deleted
     */
    public function deleteTransform(ImageTransformData $transform): bool
    {
        return $this->service()->deleteTransform($transform);
    }

    /**
     * Handle transform being deleted.
     */
    public function handleDeletedTransform(ConfigEvent $event): void
    {
        $this->service()->handleDeletedTransform($event);
    }

    /**
     * Eager-loads transform indexes the given list of assets.
     *
     * @param  array  $assets  The assets or asset data to eager-load transforms for
     * @param  array  $transforms  The transform definitions to eager-load
     */
    public function eagerLoadTransforms(array $assets, array $transforms): void
    {
        $transforms = array_map(function(mixed $transform): mixed {
            if ($transform instanceof LegacyImageTransform) {
                return $transform->getTransformer() === LegacyImageTransform::DEFAULT_TRANSFORMER
                    ? $transform
                    : ['driver' => $transform->getTransformer(), ...$transform->getOperations()];
            }

            if (is_array($transform) && array_key_exists('transformer', $transform)) {
                $transformer = $transform['transformer'];
                unset($transform['transformer']);

                if ($transformer !== null && $transformer !== LegacyImageTransform::DEFAULT_TRANSFORMER) {
                    $transform['driver'] = $transformer;
                }
            }

            return $transform;
        }, $transforms);

        app(AssetTransforms::class)->preload($assets, $transforms);
    }

    /**
     * @template T of ImageTransformerInterface
     *
     * @param  class-string<T>  $type
     * @return T
     */
    public function getImageTransformer(string $type, array $config = []): ImageTransformerInterface
    {
        if (array_key_exists($type, $this->imageTransformers)) {
            return $this->imageTransformers[$type];
        }

        if (!is_subclass_of($type, ImageTransformerInterface::class)) {
            throw new ImageTransformException("Invalid image transformer: $type");
        }

        return $this->imageTransformers[$type] = new $type($config);
    }

    /**
     * Delete *ALL* transform data (including thumbs and sources) associated with the Asset.
     */
    public function deleteAllTransformData(Asset $asset): void
    {
        $this->deleteResizedAssetVersion($asset);
        $this->deleteCreatedTransformsForAsset($asset);
        File::delete(Path::assetSources($asset->id . '.' . pathinfo($asset->getFilename(), PATHINFO_EXTENSION)));
    }

    /**
     * Delete all the generated thumbnails for the Asset.
     */
    public function deleteResizedAssetVersion(Asset $asset): void
    {
        $dir = Path::imageEditorSources((string) $asset->id);

        if (!file_exists($dir)) {
            return;
        }

        $files = glob($dir . '/[0-9]*/' . $asset->id . '.[a-z]*');

        if (!is_array($files)) {
            Log::info("Could not list files in {$dir} when deleting resized asset versions.");

            return;
        }

        foreach ($files as $path) {
            if (!File::delete($path)) {
                Log::warning("Unable to delete the asset thumbnail \"{$path}\".", [__METHOD__]);
            }
        }
    }

    /**
     * Delete created transforms for an Asset.
     */
    public function deleteCreatedTransformsForAsset(Asset $asset): void
    {
        app(AssetTransforms::class)->invalidate($asset);
    }

    /**
     * Return all available image transformers.
     *
     * @return string[]
     *
     * @phpstan-return class-string<ImageTransformerInterface>[]
     */
    public function getAllImageTransformers(): array
    {
        return app(ImageTransformers::class)->types()->all();
    }

    public static function registerEvents(): void
    {
        EventFacade::listen(TransformSaving::class, function(TransformSaving $event) {
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

        EventFacade::listen(TransformDeleting::class, function(TransformDeleting $event) {
            if (!Craft::$app->getImageTransforms()->hasEventHandlers(self::EVENT_BEFORE_DELETE_IMAGE_TRANSFORM)) {
                return;
            }

            Craft::$app->getImageTransforms()->trigger(self::EVENT_BEFORE_DELETE_IMAGE_TRANSFORM, new ImageTransformEvent([
                'imageTransform' => $event->transform,
            ]));
        });

        EventFacade::listen(TransformDeletionApplying::class, function(TransformDeletionApplying $event) {
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

        EventFacade::listen(AssetTransformsInvalidating::class, function(AssetTransformsInvalidating $event) {
            if (!Craft::$app->getImageTransforms()->hasEventHandlers(self::EVENT_BEFORE_INVALIDATE_ASSET_TRANSFORMS)) {
                return;
            }

            Craft::$app->getImageTransforms()->trigger(self::EVENT_BEFORE_INVALIDATE_ASSET_TRANSFORMS, new AssetEvent([
                'asset' => $event->asset,
            ]));
        });
    }

    /** @internal */
    public static function finalizeRegistrationEvents(): void
    {
        $transformers = app(ImageTransformers::class);
        $existingTransformers = $transformers->types();
        TypeRegistryCompatibility::reconcile($transformers, Craft::$app->getImageTransforms(), self::EVENT_REGISTER_IMAGE_TRANSFORMERS);

        foreach ($transformers->types()->diff($existingTransformers) as $transformer) {
            app(AssetTransforms::class)->extend($transformer, fn() => new LegacyImageTransformerDriver($transformer));
        }
    }

    private function service(): ImageTransformsService
    {
        return app(ImageTransformsService::class);
    }

    private function toLegacyTransform(?ImageTransformData $transform): ?LegacyImageTransform
    {
        if ($transform === null) {
            return null;
        }

        return new LegacyImageTransform([
            ...$transform->getConfig(),
            'id' => $transform->id,
            'uid' => $transform->uid,
            'parameterChangeTime' => $transform->parameterChangeTime,
        ]);
    }
}
