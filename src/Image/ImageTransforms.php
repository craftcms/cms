<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Events\TransformDeleted;
use CraftCms\Cms\Image\Events\TransformDeleting;
use CraftCms\Cms\Image\Events\TransformDeletionApplying;
use CraftCms\Cms\Image\Events\TransformSaved;
use CraftCms\Cms\Image\Events\TransformSaving;
use CraftCms\Cms\Image\Models\ImageTransform as ImageTransformModel;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Singleton]
class ImageTransforms
{
    /** @var Collection<int, ImageTransform>|null */
    private ?Collection $transforms = null;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly ElementCaches $elementCaches,
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
            $parameters = Arr::only($data, ImageTransform::CORE_PARAMETERS);
            $customParameters = $data['parameters'];

            $transformModel->name = $data['name'];
            $transformModel->handle = $data['handle'];
            $storedCustomParameters = $transformModel->getAttribute('parameters');
            $customParametersChanged = (is_array($storedCustomParameters) ? $storedCustomParameters : []) !== $customParameters;

            $dimensionsChanged = $transformModel->width !== $parameters['width'] || $transformModel->height !== $parameters['height'];
            $modeChanged = $transformModel->mode !== $parameters['mode'] || $transformModel->position !== $parameters['position'];
            $qualityChanged = $transformModel->quality !== $parameters['quality'];
            $interlaceChanged = $transformModel->interlace !== $parameters['interlace'];
            $fillChanged = $transformModel->fill !== $parameters['fill'];
            $upscaleChanged = ($transformModel->upscale !== null ? (bool) $transformModel->upscale : null) !== $parameters['upscale'];

            if ($dimensionsChanged || $modeChanged || $qualityChanged || $interlaceChanged || $fillChanged || $upscaleChanged || $customParametersChanged) {
                $transformModel->parameterChangeTime = Query::prepareDateForDb(now());
            }

            $transformModel->mode = $parameters['mode'];
            $transformModel->position = $parameters['position'];
            $transformModel->width = $parameters['width'];
            $transformModel->height = $parameters['height'];
            $transformModel->quality = $parameters['quality'];
            $transformModel->interlace = $parameters['interlace'];
            $transformModel->format = $parameters['format'];
            $transformModel->fill = $parameters['fill'];
            $transformModel->upscale = $parameters['upscale'];
            $transformModel->setAttribute('parameters', $customParameters ?: null);
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
                'mode',
                'position',
                'height',
                'width',
                'format',
                'quality',
                'interlace',
                'fill',
                'upscale',
                'parameters',
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
