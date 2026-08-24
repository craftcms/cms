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
use craft\imagetransforms\ImageTransformer;
use craft\models\ImageTransform as LegacyImageTransform;
use CraftCms\Cms\Asset\AssetProcessors;
use CraftCms\Cms\Asset\Data\AssetProcessor;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Asset\Exceptions\InvalidAssetTransformException;
use CraftCms\Cms\Image\Data\ImageTransform as ImageTransformData;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\Events\TransformDeleted;
use CraftCms\Cms\Image\Events\TransformDeleting;
use CraftCms\Cms\Image\Events\TransformDeletionApplying;
use CraftCms\Cms\Image\Events\TransformSaved;
use CraftCms\Cms\Image\Events\TransformSaving;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Image\ImageTransforms as ImageTransformsService;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use CraftCms\Yii2Adapter\Asset\ImageTransformers;
use CraftCms\Yii2Adapter\Asset\LegacyImageTransformerDriver;
use CraftCms\Yii2Adapter\Event\TypeRegistryCompatibility;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
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
        $requestsByTransformer = [];

        foreach ($assets as $asset) {
            $coreTransforms = [];

            foreach ($transforms as $transform) {
                [$transformer, $definition] = $this->legacyTransformer($transform);
                $request = $transformer === null
                    ? null
                    : $this->legacyTransformRequest($asset, $definition, $transformer, false);

                if ($request === null) {
                    $coreTransforms[] = $definition;

                    continue;
                }

                $requestsByTransformer[$transformer][] = $request;
            }

            if ($coreTransforms !== []) {
                app(AssetProcessors::class)->preload([$asset], $coreTransforms);
            }
        }

        foreach ($requestsByTransformer as $transformer => $requests) {
            app(ImageTransformers::class)->register($transformer);
            new LegacyImageTransformerDriver($transformer)->preloadAssetTransforms($requests);
        }
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
        app(AssetProcessors::class)->invalidate($asset);
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
            if (Craft::$app->getImageTransforms()->hasEventHandlers(self::EVENT_BEFORE_INVALIDATE_ASSET_TRANSFORMS)) {
                Craft::$app->getImageTransforms()->trigger(self::EVENT_BEFORE_INVALIDATE_ASSET_TRANSFORMS, new AssetEvent([
                    'asset' => $event->asset,
                ]));
            }

            foreach (app(ImageTransformers::class)->types() as $transformer) {
                if ($transformer === ImageTransformer::class) {
                    continue;
                }

                new LegacyImageTransformerDriver($transformer)
                    ->invalidateAssetTransforms($event->asset);
            }
        });
    }

    /** @internal */
    public static function finalizeRegistrationEvents(): void
    {
        $transformers = app(ImageTransformers::class);
        TypeRegistryCompatibility::reconcile($transformers, Craft::$app->getImageTransforms(), self::EVENT_REGISTER_IMAGE_TRANSFORMERS);
    }

    public function transformAsset(
        Asset $asset,
        #[\SensitiveParameter] mixed $definition,
        bool $immediately,
    ): AssetTransformResult {
        [$transformer, $definition] = $this->legacyTransformer($definition);

        if ($transformer === null) {
            return app(AssetProcessors::class)->transform($asset, $definition, $immediately);
        }

        app(ImageTransformers::class)->register($transformer);
        $request = $this->legacyTransformRequest($asset, $definition, $transformer, $immediately);

        if ($request === null) {
            return app(AssetProcessors::class)->transform($asset, $definition, $immediately);
        }

        return new LegacyImageTransformerDriver($transformer)->transform($request);
    }

    /** @param class-string<ImageTransformerInterface> $transformer */
    private function legacyAssetProcessor(string $transformer): AssetProcessor
    {
        return new AssetProcessor([
            'uid' => Uuid::uuid5(Uuid::NAMESPACE_URL, "craftcms:legacy-image-transformer:$transformer")->toString(),
            'name' => $transformer,
            'handle' => 'legacy_' . substr(sha1($transformer), 0, 16),
            'driver' => $transformer,
        ]);
    }

    /** @param class-string<ImageTransformerInterface> $transformer */
    private function legacyTransformRequest(
        Asset $asset,
        #[\SensitiveParameter] mixed $definition,
        string $transformer,
        bool $immediately,
    ): ?AssetTransformRequest {
        if ($asset->getVolume()->getAssetProcessorHandle(false) || $this->hasTransformerOverride($definition)) {
            return null;
        }

        $assetProcessor = $this->legacyAssetProcessor($transformer);

        return new AssetTransformRequest(
            asset: $asset,
            processor: $assetProcessor,
            operations: $this->transformOperations($definition, (string) $assetProcessor->uid),
            immediately: $immediately,
        );
    }

    /** @return array{class-string<ImageTransformerInterface>|null, mixed} */
    private function legacyTransformer(mixed $definition): array
    {
        $transformer = $definition instanceof LegacyImageTransform
            ? $definition->getTransformer()
            : (is_array($definition) ? ($definition['transformer'] ?? null) : null);

        if (!is_string($transformer) || !is_subclass_of($transformer, ImageTransformerInterface::class)) {
            return [null, $definition];
        }

        if (is_array($definition)) {
            unset($definition['transformer']);
        }

        return [
            $transformer === LegacyImageTransform::DEFAULT_TRANSFORMER ? null : $transformer,
            $definition,
        ];
    }

    private function hasTransformerOverride(mixed $definition): bool
    {
        if (!is_array($definition)) {
            return false;
        }

        if (array_key_exists('transformer', $definition)) {
            return true;
        }

        return array_key_exists('transform', $definition)
            && $this->hasTransformerOverride($definition['transform']);
    }

    /** @return array<string, mixed> */
    private function transformOperations(mixed $definition, string $transformerUid): array
    {
        try {
            $transform = ImageTransformHelper::normalizeTransform($definition);
        } catch (ImageTransformException|InvalidArgumentException $exception) {
            throw new InvalidAssetTransformException($exception->getMessage(), previous: $exception);
        }

        if ($transform === null) {
            throw new InvalidAssetTransformException('An Asset Transform definition must be an array, object, or named transform handle.');
        }

        return $transform->getOperations($transformerUid);
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
