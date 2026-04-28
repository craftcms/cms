<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\ApplyingVolumeDelete;
use CraftCms\Cms\Asset\Events\DeletingVolume;
use CraftCms\Cms\Asset\Events\SavingVolume;
use CraftCms\Cms\Asset\Events\VolumeDeleted;
use CraftCms\Cms\Asset\Events\VolumeSaved;
use CraftCms\Cms\Asset\Models\Volume as VolumeModel;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

use function CraftCms\Cms\t;

#[Singleton]
class Volumes
{
    /** @var Collection<int, Volume>|null */
    private ?Collection $volumes = null;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly Assets $assets,
        private readonly Folders $folders,
        private readonly ElementCaches $elementCaches,
    ) {}

    /** @return Collection<int, int> */
    public function getAllVolumeIds(): Collection
    {
        return $this->getAllVolumes()->pluck('id')->values();
    }

    /** @return Collection<int, int> */
    public function getViewableVolumeIds(): Collection
    {
        return $this->getViewableVolumes()->pluck('id')->values();
    }

    /** @return Collection<int, Volume> */
    public function getViewableVolumes(): Collection
    {
        if (app()->runningInConsole()) {
            return $this->getAllVolumes();
        }

        return $this->getAllVolumes()
            ->filter(fn (Volume $volume) => Gate::check("viewAssets:$volume->uid"))
            ->values();
    }

    public function getTotalVolumes(): int
    {
        return $this->getAllVolumes()->count();
    }

    public function getTotalViewableVolumes(): int
    {
        return $this->getViewableVolumes()->count();
    }

    /** @return Collection<int, Volume> */
    public function getAllVolumes(): Collection
    {
        return $this->volumes();
    }

    public function getVolumeById(int $volumeId): ?Volume
    {
        return $this->volumes()->firstWhere('id', $volumeId);
    }

    public function getVolumeByUid(string $volumeUid): ?Volume
    {
        return $this->volumes()->firstWhere('uid', $volumeUid);
    }

    public function getVolumeByHandle(string $handle): ?Volume
    {
        return $this->volumes()->firstWhere('handle', $handle);
    }

    public function getTemporaryVolume(): Volume
    {
        $volume = new Volume([
            'name' => t('Temporary Uploads'),
        ]);

        $fs = $this->assets->getTempAssetUploadFs();
        $volume->setFs($fs);

        return $volume;
    }

    public function getUserPhotoVolume(): ?Volume
    {
        $uid = $this->projectConfig->get('users.photoVolumeUid') ?? '';

        return $this->getVolumeByUid($uid);
    }

    public function saveVolume(Volume $volume, bool $runValidation = true): bool
    {
        $isNewVolume = ! $volume->id;

        event(new SavingVolume(
            volume: $volume,
            isNew: $isNewVolume,
        ));

        if ($runValidation && ! $volume->validate()) {
            Log::info('Volume not saved due to validation error.', [__METHOD__]);

            return false;
        }

        if ($isNewVolume) {
            $volume->uid ??= Str::uuid()->toString();
            $volume->sortOrder = DB::table(Table::VOLUMES)->max('sortOrder') + 1;
        } elseif (! $volume->uid) {
            $volume->uid = DB::table(Table::VOLUMES)->uidById($volume->id);
        }

        $configPath = ProjectConfig::PATH_VOLUMES.'.'.$volume->uid;
        $this->projectConfig->set($configPath, $volume->getConfig(), "Save the \u{201c}{$volume->handle}\u{201d} volume");

        if ($isNewVolume) {
            $volume->id = DB::table(Table::VOLUMES)->idByUid($volume->uid);
        }

        return true;
    }

    public function handleChangedVolume(ConfigEvent $event): void
    {
        $volumeUid = $event->tokenMatches[0];
        $data = $event->newValue;

        ProjectConfigHelper::ensureAllFilesystemsProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        DB::beginTransaction();
        try {
            $volumeModel = $this->getVolumeModel($volumeUid, true);
            $isNewVolume = ! $volumeModel->exists;

            $volumeModel->name = $data['name'];
            $volumeModel->handle = $data['handle'];
            $volumeModel->fs = $data['fs'] ?? null;
            $volumeModel->subpath = $data['subpath'] ?? null;
            $volumeModel->transformFs = $data['transformFs'] ?? null;
            $volumeModel->transformSubpath = $data['transformSubpath'] ?? null;
            $volumeModel->sortOrder = $data['sortOrder'];
            $volumeModel->titleTranslationMethod = $data['titleTranslationMethod'] ?? TranslationMethod::Site->value;
            $volumeModel->titleTranslationKeyFormat = $data['titleTranslationKeyFormat'] ?? null;
            $volumeModel->altTranslationMethod = $data['altTranslationMethod'] ?? TranslationMethod::None->value;
            $volumeModel->altTranslationKeyFormat = $data['altTranslationKeyFormat'] ?? null;
            $volumeModel->uid = $volumeUid;

            if (! empty($data['fieldLayouts'])) {
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $volumeModel->fieldLayoutId;
                $layout->type = Asset::class;
                $layout->uid = key($data['fieldLayouts']);
                app(Fields::class)->saveLayout($layout, false);
                $volumeModel->fieldLayoutId = $layout->id;
            } elseif ($volumeModel->fieldLayoutId) {
                app(Fields::class)->deleteLayoutById($volumeModel->fieldLayoutId);
                $volumeModel->fieldLayoutId = null;
            }

            if ($wasTrashed = (bool) $volumeModel->dateDeleted) {
                $volumeModel->dateDeleted = null;
            }

            $volumeModel->save();

            $rootFolder = $this->folders->findFolder([
                'volumeId' => $volumeModel->id,
                'parentId' => ':empty:',
            ]);

            if ($rootFolder === null) {
                VolumeFolderModel::create([
                    'volumeId' => $volumeModel->id,
                    'parentId' => null,
                    'path' => '',
                    'name' => $volumeModel->name,
                ]);
            } else {
                $rootFolder->name = $volumeModel->name;
                $this->folders->storeFolderModel($rootFolder);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Clear caches
        $this->volumes = null;

        if ($wasTrashed) {
            /** @var Asset[] $assets */
            $assets = Asset::find()
                ->volumeId($volumeModel->id)
                ->trashed()
                ->where('assets.deletedWithVolume', true)
                ->all();

            Elements::restoreElements($assets);
        }

        event(new VolumeSaved(
            volume: $this->getVolumeById($volumeModel->id),
            isNew: $isNewVolume,
        ));

        $this->elementCaches->invalidateForElementType(Asset::class);
    }

    /** @param int[] $volumeIds */
    public function reorderVolumes(array $volumeIds): bool
    {
        $uidsByIds = DB::table(Table::VOLUMES)->uidsByIds($volumeIds);

        foreach ($volumeIds as $volumeOrder => $volumeId) {
            if (! empty($uidsByIds[$volumeId])) {
                $volumeUid = $uidsByIds[$volumeId];
                $this->projectConfig->set(
                    ProjectConfig::PATH_VOLUMES.'.'.$volumeUid.'.sortOrder',
                    $volumeOrder + 1,
                    'Reorder volumes',
                );
            }
        }

        return true;
    }

    public function deleteVolumeById(int $volumeId): bool
    {
        $volume = $this->getVolumeById($volumeId);

        if (! $volume) {
            return false;
        }

        return $this->deleteVolume($volume);
    }

    public function deleteVolume(Volume $volume): bool
    {
        event(new DeletingVolume(volume: $volume));

        $this->projectConfig->remove(
            ProjectConfig::PATH_VOLUMES.'.'.$volume->uid,
            "Delete the \u{201c}{$volume->handle}\u{201d} volume",
        );

        return true;
    }

    public function handleDeletedVolume(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $volumeModel = $this->getVolumeModel($uid);

        if (! $volumeModel->exists) {
            return;
        }

        $volume = $this->getVolumeById($volumeModel->id);

        event(new ApplyingVolumeDelete(volume: $volume));

        DB::beginTransaction();
        try {
            /** @var Asset[] $assets */
            $assets = Asset::find()
                ->volumeId($volumeModel->id)
                ->status(null)
                ->all();

            foreach ($assets as $asset) {
                $asset->deletedWithVolume = true;
                $asset->keepFileOnDelete = true;
                Elements::deleteElement($asset);
            }

            if ($volumeModel->fieldLayoutId) {
                app(Fields::class)->deleteLayoutById($volumeModel->fieldLayoutId);
            }

            $volumeModel->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->volumes = null;

        event(new VolumeDeleted(volume: $volume));

        $this->elementCaches->invalidateForElementType(Asset::class);
    }

    /**
     * @return Collection<int, Volume>
     */
    private function volumes(): Collection
    {
        return $this->volumes ?? $this->volumes = DB::table(Table::VOLUMES)
            ->whereNull('dateDeleted')
            ->orderBy('sortOrder')
            ->get()
            ->map(fn ($result) => new Volume(
                Arr::except((array) $result, ['dateCreated', 'dateUpdated', 'dateDeleted'])
            ))
            ->values();
    }

    private function getVolumeModel(string $uid, bool $withTrashed = false): VolumeModel
    {
        return VolumeModel::query()
            ->withTrashed($withTrashed)
            ->where('uid', $uid)
            ->firstOrNew();
    }

    public function reset(): void
    {
        $this->volumes = null;
    }
}
