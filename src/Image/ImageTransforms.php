<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Image\Contracts\EagerImageTransformerInterface;
use CraftCms\Cms\Image\Contracts\ImageTransformerInterface;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\Events\TransformDeleted;
use CraftCms\Cms\Image\Events\TransformDeleting;
use CraftCms\Cms\Image\Events\TransformDeletionApplying;
use CraftCms\Cms\Image\Events\TransformSaved;
use CraftCms\Cms\Image\Events\TransformSaving;
use CraftCms\Cms\Image\Models\ImageTransform as ImageTransformModel;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

#[Singleton]
class ImageTransforms
{
    /** @var Collection<int, ImageTransform>|null */
    private ?Collection $transforms = null;

    /** @var array<class-string<ImageTransformerInterface>, ImageTransformerInterface> */
    private array $imageTransformers = [];

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly ElementCaches $elementCaches,
        private readonly ImageTransformers $imageTransformerTypes,
    ) {}

    /**
     * Returns all named image transforms.
     *
     * @return Collection<int, ImageTransform>
     */
    public function getAllTransforms(): Collection
    {
        return $this->transforms();
    }

    public function getTransformByHandle(string $handle): ?ImageTransform
    {
        return $this->transforms()->firstWhere('handle', $handle);
    }

    public function getTransformById(int $id): ?ImageTransform
    {
        return $this->transforms()->firstWhere('id', $id);
    }

    public function getTransformByUid(string $uid): ?ImageTransform
    {
        return $this->transforms()->firstWhere('uid', $uid);
    }

    public function saveTransform(ImageTransform $transform, bool $runValidation = true): bool
    {
        $isNewTransform = ! $transform->id;

        event(new TransformSaving(
            transform: $transform,
            isNew: $isNewTransform,
        ));

        if ($runValidation && ! $transform->validate()) {
            Log::info('Image transform not saved due to validation error.', [__METHOD__]);

            return false;
        }

        if ($isNewTransform) {
            $transform->uid ??= Str::uuid()->toString();
        } elseif (! $transform->uid) {
            $transform->uid = DB::table(Table::IMAGETRANSFORMS)->uidById($transform->id);
        }

        $configPath = ProjectConfig::PATH_IMAGE_TRANSFORMS.'.'.$transform->uid;
        $this->projectConfig->set($configPath, $transform->getConfig(), "Save the “{$transform->handle}” image transform");

        if ($isNewTransform) {
            $transform->id = DB::table(Table::IMAGETRANSFORMS)->idByUid($transform->uid);
        }

        return true;
    }

    public function handleChangedTransform(ConfigEvent $event): void
    {
        $transformUid = $event->tokenMatches[0];
        $data = ImageTransform::fromConfig($event->newValue)->getConfig();

        [$transformModel, $isNewTransform] = DB::transaction(function () use ($transformUid, $data) {
            $transformModel = $this->getImageTransformModel($transformUid);
            $isNewTransform = ! $transformModel->exists;
            $operations = $data['operations'];
            $customOperations = Arr::except($operations, ImageTransform::CORE_OPERATIONS);

            $transformModel->name = $data['name'];
            $transformModel->handle = $data['handle'];
            $driverChanged = $transformModel->driver !== $data['driver'];
            $storedCustomOperations = $transformModel->getAttribute('operations');
            $customOperationsChanged = (is_array($storedCustomOperations) ? $storedCustomOperations : []) !== $customOperations;

            $dimensionsChanged = $transformModel->width !== $operations['width'] || $transformModel->height !== $operations['height'];
            $modeChanged = $transformModel->mode !== $operations['mode'] || $transformModel->position !== $operations['position'];
            $qualityChanged = $transformModel->quality !== $operations['quality'];
            $interlaceChanged = $transformModel->interlace !== $operations['interlace'];
            $fillChanged = $transformModel->fill !== $operations['fill'];
            $upscaleChanged = ($transformModel->upscale !== null ? (bool) $transformModel->upscale : null) !== $operations['upscale'];

            if ($dimensionsChanged || $modeChanged || $qualityChanged || $interlaceChanged || $fillChanged || $upscaleChanged || $driverChanged || $customOperationsChanged) {
                $transformModel->parameterChangeTime = Query::prepareDateForDb(now());
            }

            $transformModel->driver = $data['driver'];
            $transformModel->mode = $operations['mode'];
            $transformModel->position = $operations['position'];
            $transformModel->width = $operations['width'];
            $transformModel->height = $operations['height'];
            $transformModel->quality = $operations['quality'];
            $transformModel->interlace = $operations['interlace'];
            $transformModel->format = $operations['format'];
            $transformModel->fill = $operations['fill'];
            $transformModel->upscale = $operations['upscale'];
            $transformModel->setAttribute('operations', $customOperations ?: null);
            $transformModel->uid = $transformUid;

            $transformModel->save();

            return [$transformModel, $isNewTransform];
        });

        $this->transforms = null;

        event(new TransformSaved(
            transform: $this->getTransformById($transformModel->id),
            isNew: $isNewTransform,
        ));

        $this->elementCaches->invalidateForElementType(Asset::class);
    }

    public function deleteTransformById(int $id): bool
    {
        $transform = $this->getTransformById($id);

        if (! $transform) {
            return false;
        }

        return $this->deleteTransform($transform);
    }

    public function deleteTransform(ImageTransform $transform): bool
    {
        event(new TransformDeleting(transform: $transform));

        $this->projectConfig->remove(
            ProjectConfig::PATH_IMAGE_TRANSFORMS.'.'.$transform->uid,
            "Delete the “{$transform->handle}” image transform",
        );

        return true;
    }

    public function handleDeletedTransform(ConfigEvent $event): void
    {
        $transformUid = $event->tokenMatches[0];

        $transform = $this->getTransformByUid($transformUid);

        if (! $transform) {
            return;
        }

        event(new TransformDeletionApplying(transform: $transform));

        DB::table(Table::IMAGETRANSFORMS)->where('uid', $transformUid)->delete();

        // Clear caches
        $this->transforms = null;

        event(new TransformDeleted(transform: $transform));

        $this->elementCaches->invalidateForElementType(Asset::class);
    }

    /**
     * Eager-loads transform indexes for the given list of assets.
     *
     * You can include `srcset`-style sizes (e.g. `100w` or `2x`) following a normal transform definition, for example:
     *
     * ```php
     * [['width' => 1000, 'height' => 600], '1.5x', '2x', '3x']
     * ```
     *
     * When a `srcset`-style size is encountered, the preceding normal transform definition will be used as a
     * reference when determining the resulting transform dimensions.
     *
     * @param  array<int,mixed>  $transforms  The transform definitions to eager-load
     * @param  Asset[]  $assets  The assets to eager-load transforms for
     */
    public function eagerLoadTransforms(array $assets, array $transforms): void
    {
        if (empty($assets) || empty($transforms)) {
            return;
        }

        $transformsByTransformer = [];

        /** @var ImageTransform|null $refTransform */
        $refTransform = null;

        foreach ($transforms as $transform) {
            // Is this a srcset-style size (2x, 100w, etc.)?
            try {
                [$sizeValue, $sizeUnit] = AssetsHelper::parseSrcsetSize($transform);
            } catch (InvalidArgumentException) {
                $sizeValue = $sizeUnit = null;
            }

            if (isset($sizeValue, $sizeUnit)) {
                if ($refTransform === null || ! $refTransform->width) {
                    throw new InvalidArgumentException("Can’t eager-load transform “{$transform}” without a prior transform that specifies the base width");
                }

                $transform = new ImageTransform(
                    $refTransform->toArray(),
                );

                unset($transform->name, $transform->handle);

                if ($sizeUnit === 'w') {
                    $transform->width = (int) $sizeValue;
                } else {
                    $transform->width = (int) ceil($refTransform->width * $sizeValue);
                }

                // Only set the height if the reference transform has a height set on it
                if ($refTransform->height) {
                    if ($sizeUnit === 'w') {
                        $transform->height = (int) ceil($refTransform->height * $transform->width / $refTransform->width);
                    } else {
                        $transform->height = (int) ceil($refTransform->height * $sizeValue);
                    }
                }
            }

            $transform = ImageTransformHelper::normalizeTransform($transform);
            $transformsByTransformer[$transform->getTransformer()][] = $transform;

            if (! isset($sizeValue)) {
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
     * Returns an image transformer instance for the given class.
     *
     * @template T of ImageTransformerInterface
     *
     * @param  class-string<T>  $class
     * @param  array<string,mixed>  $config
     * @return T
     *
     * @throws ImageTransformException
     */
    public function getImageTransformer(string $class, array $config = []): ImageTransformerInterface
    {
        if (array_key_exists($class, $this->imageTransformers)) {
            return $this->imageTransformers[$class];
        }

        if (! is_subclass_of($class, ImageTransformerInterface::class)) {
            throw new ImageTransformException("Invalid image transformer: $class");
        }

        return $this->imageTransformers[$class] = new $class($config);
    }

    /**
     * Returns all available image transformer class names.
     *
     * @return class-string<ImageTransformerInterface>[]
     */
    public function getAllImageTransformers(): array
    {
        return $this->imageTransformerTypes->types()->all();
    }

    /**
     * Deletes ALL transform data (including thumbs and sources) associated with the asset.
     */
    public function deleteAllTransformData(Asset $asset): void
    {
        $this->deleteResizedAssetVersion($asset);
        $this->deleteCreatedTransformsForAsset($asset);

        $file = Path::assetSources($asset->id.'.'.pathinfo($asset->getFilename(), PATHINFO_EXTENSION));

        File::delete($file);
    }

    public function deleteResizedAssetVersion(Asset $asset): void
    {
        $dirs = [
            Path::imageEditorSources((string) $asset->id),
        ];

        foreach ($dirs as $dir) {
            if (file_exists($dir)) {
                $files = glob($dir.'/[0-9]*/'.$asset->id.'.[a-z]*');

                if (! is_array($files)) {
                    Log::info('Could not list files in '.$dir.' when deleting resized asset versions.');

                    continue;
                }

                foreach ($files as $path) {
                    if (! File::delete($path)) {
                        Log::warning("Unable to delete the asset thumbnail \"$path\".", [__METHOD__]);
                    }
                }
            }
        }
    }

    public function deleteCreatedTransformsForAsset(Asset $asset): void
    {
        event(new AssetTransformsInvalidating(asset: $asset));
    }

    /**
     * Returns a memoized collection of all named image transforms.
     *
     * @return Collection<int, ImageTransform>
     */
    private function transforms(): Collection
    {
        return $this->transforms ?? $this->transforms = ImageTransformModel::query()
            ->select([
                'id',
                'name',
                'handle',
                'driver',
                'mode',
                'position',
                'height',
                'width',
                'format',
                'quality',
                'interlace',
                'fill',
                'upscale',
                'operations',
                'parameterChangeTime',
                'uid',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (ImageTransformModel $model) => new ImageTransform(
                Arr::except($model->toArray(), ['dateCreated', 'dateUpdated', 'dateDeleted'])
            ))
            ->values();
    }

    private function getImageTransformModel(string $uid): ImageTransformModel
    {
        return ImageTransformModel::query()
            ->where('uid', $uid)
            ->firstOrNew();
    }

    public function reset(): void
    {
        $this->transforms = null;
    }
}
