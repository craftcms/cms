<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use Craft;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Image\Contracts\EagerImageTransformerInterface;
use CraftCms\Cms\Image\Contracts\ImageTransformerInterface;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Events\ApplyingTransformDelete;
use CraftCms\Cms\Image\Events\DeletingTransform;
use CraftCms\Cms\Image\Events\InvalidatingAssetTransforms;
use CraftCms\Cms\Image\Events\RegisterImageTransformers;
use CraftCms\Cms\Image\Events\SavingTransform;
use CraftCms\Cms\Image\Events\TransformDeleted;
use CraftCms\Cms\Image\Events\TransformSaved;
use CraftCms\Cms\Image\Models\ImageTransform as ImageTransformModel;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use DateTime;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

#[Singleton]
final class ImageTransforms
{
    /** @var Collection<int, ImageTransform>|null */
    private ?Collection $transforms = null;

    /** @var array<class-string<ImageTransformerInterface>, ImageTransformerInterface> */
    private array $imageTransformers = [];

    public function __construct(
        private readonly ProjectConfig $projectConfig,
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

        event(new SavingTransform(
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
        $data = $event->newValue;

        DB::beginTransaction();

        try {
            $transformModel = $this->getImageTransformModel($transformUid);
            $isNewTransform = ! $transformModel->exists;

            $transformModel->name = $data['name'];
            $transformModel->handle = $data['handle'];

            $heightChanged = $transformModel->width !== ($data['width'] ?? null) || $transformModel->height !== ($data['height'] ?? null);
            $modeChanged = $transformModel->mode !== $data['mode'] || $transformModel->position !== $data['position'];
            $qualityChanged = $transformModel->quality !== ($data['quality'] ?? null);
            $interlaceChanged = $transformModel->interlace !== $data['interlace'];
            $fillChanged = $transformModel->fill !== ($data['fill'] ?? null);
            $upscaleChanged = ($transformModel->upscale !== null ? (bool) $transformModel->upscale : null) !== ($data['upscale'] ?? null);

            if ($heightChanged || $modeChanged || $qualityChanged || $interlaceChanged || $fillChanged || $upscaleChanged) {
                $transformModel->parameterChangeTime = Query::prepareDateForDb(new DateTime);
            }

            $transformModel->mode = $data['mode'];
            $transformModel->position = $data['position'];
            $transformModel->width = $data['width'] ?? null;
            $transformModel->height = $data['height'] ?? null;
            $transformModel->quality = $data['quality'] ?? null;
            $transformModel->interlace = $data['interlace'];
            $transformModel->format = $data['format'] ?? null;
            $transformModel->fill = $data['fill'] ?? null;
            $transformModel->upscale = $data['upscale'] ?? true;
            $transformModel->uid = $transformUid;

            $transformModel->save();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Clear caches
        $this->transforms = null;

        event(new TransformSaved(
            transform: $this->getTransformById($transformModel->id),
            isNew: $isNewTransform,
        ));

        Craft::$app->getElements()->invalidateCachesForElementType(Asset::class);
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
        event(new DeletingTransform(transform: $transform));

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

        event(new ApplyingTransformDelete(transform: $transform));

        DB::table(Table::IMAGETRANSFORMS)->where('uid', $transformUid)->delete();

        // Clear caches
        $this->transforms = null;

        event(new TransformDeleted(transform: $transform));

        Craft::$app->getElements()->invalidateCachesForElementType(Asset::class);
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
     * @param  array  $transforms  The transform definitions to eager-load
     * @param  Asset[]  $assets  The assets to eager-load transforms for
     */
    public function eagerLoadTransforms(array $transforms, array $assets): void
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

        return $this->imageTransformers[$class] = Craft::createObject(array_merge(['class' => $class], $config));
    }

    /**
     * Returns all available image transformer class names.
     *
     * @return class-string<ImageTransformerInterface>[]
     */
    public function getAllImageTransformers(): array
    {
        $transformers = [
            ImageTransformer::class,
        ];

        event($event = new RegisterImageTransformers(types: $transformers));

        return $event->types;
    }

    /**
     * Deletes ALL transform data (including thumbs and sources) associated with the asset.
     */
    public function deleteAllTransformData(Asset $asset): void
    {
        $this->deleteResizedAssetVersion($asset);
        $this->deleteCreatedTransformsForAsset($asset);

        $file = Craft::$app->getPath()->getAssetSourcesPath().DIRECTORY_SEPARATOR.$asset->id.'.'.pathinfo($asset->getFilename(), PATHINFO_EXTENSION);

        if (file_exists($file)) {
            FileHelper::unlink($file);
        }
    }

    public function deleteResizedAssetVersion(Asset $asset): void
    {
        $dirs = [
            Craft::$app->getPath()->getImageEditorSourcesPath().'/'.$asset->id,
        ];

        foreach ($dirs as $dir) {
            if (file_exists($dir)) {
                $files = glob($dir.'/[0-9]*/'.$asset->id.'.[a-z]*');

                if (! is_array($files)) {
                    Log::info('Could not list files in '.$dir.' when deleting resized asset versions.');

                    continue;
                }

                foreach ($files as $path) {
                    if (! FileHelper::unlink($path)) {
                        Log::warning("Unable to delete the asset thumbnail \"$path\".", [__METHOD__]);
                    }
                }
            }
        }
    }

    public function deleteCreatedTransformsForAsset(Asset $asset): void
    {
        event(new InvalidatingAssetTransforms(asset: $asset));

        $transformers = $this->getAllImageTransformers();

        foreach ($transformers as $type) {
            $transformer = $this->getImageTransformer($type);
            $transformer->invalidateAssetTransforms($asset);
        }
    }

    /**
     * Returns a memoized collection of all named image transforms.
     *
     * @return Collection<int, ImageTransform>
     */
    private function transforms(): Collection
    {
        return $this->transforms ?? $this->transforms = DB::table(Table::IMAGETRANSFORMS)
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
            ->orderBy('name')
            ->get()
            ->map(fn ($result) => new ImageTransform(
                Arr::except((array) $result, ['dateCreated', 'dateUpdated', 'dateDeleted'])
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
