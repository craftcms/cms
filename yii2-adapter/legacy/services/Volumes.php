<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\VolumeEvent;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Events\ApplyingVolumeDelete;
use CraftCms\Cms\Asset\Events\DeletingVolume;
use CraftCms\Cms\Asset\Events\SavingVolume;
use CraftCms\Cms\Asset\Events\VolumeDeleted;
use CraftCms\Cms\Asset\Events\VolumeSaved;
use CraftCms\Cms\Asset\Volumes as VolumesService;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use Illuminate\Support\Facades\Event as EventFacade;
use InvalidArgumentException;
use yii\base\Component;

/**
 * Volumes service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getVolumes()|`Craft::$app->getVolumes()`]].
 *
 * @property-read int[] $allVolumeIds
 * @property-read string[] $allVolumeTypes
 * @property-read int $totalVolumes
 * @property-read array $viewableVolumeIds
 * @property-read Volume[] $allVolumes
 * @property-read int[] $publicVolumeIds
 * @property-read int $totalViewableVolumes
 * @property-read Volume[] $publicVolumes
 * @property-read Volume[] $viewableVolumes
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Asset\Volumes} instead.
 */
class Volumes extends Component
{
    /**
     * @event VolumeEvent The event that is triggered before a volume is saved.
     */
    public const EVENT_BEFORE_SAVE_VOLUME = 'beforeSaveVolume';

    /**
     * @event VolumeEvent The event that is triggered after a volume is saved.
     */
    public const EVENT_AFTER_SAVE_VOLUME = 'afterSaveVolume';

    /**
     * @event VolumeEvent The event that is triggered before a volume is deleted.
     */
    public const EVENT_BEFORE_DELETE_VOLUME = 'beforeDeleteVolume';

    /**
     * @event VolumeEvent The event that is triggered before a volume delete is applied to the database.
     * @since 3.1.0
     */
    public const EVENT_BEFORE_APPLY_VOLUME_DELETE = 'beforeApplyVolumeDelete';

    /**
     * @event VolumeEvent The event that is triggered after a volume is deleted.
     */
    public const EVENT_AFTER_DELETE_VOLUME = 'afterDeleteVolume';

    /**
     * Serializer
     *
     * @since 3.5.14
     */
    public function __serialize(): array
    {
        return get_object_vars($this);
    }

    // Volumes
    // -------------------------------------------------------------------------

    /**
     * Returns all of the volume IDs.
     *
     * @return int[]
     */
    public function getAllVolumeIds(): array
    {
        return $this->service()->getAllVolumeIds()->all();
    }

    /**
     * Returns all volume IDs that are viewable by the current user.
     *
     * @return array
     */
    public function getViewableVolumeIds(): array
    {
        return $this->service()->getViewableVolumeIds()->all();
    }

    /**
     * Returns all volumes that are viewable by the current user.
     *
     * @return Volume[]
     */
    public function getViewableVolumes(): array
    {
        return $this->service()->getViewableVolumes()->all();
    }

    /**
     * Returns the total number of volumes.
     *
     * @return int
     */
    public function getTotalVolumes(): int
    {
        return $this->service()->getTotalVolumes();
    }

    /**
     * Returns the total number of volumes that are viewable by the current user.
     *
     * @return int
     */
    public function getTotalViewableVolumes(): int
    {
        return $this->service()->getTotalViewableVolumes();
    }

    /**
     * Returns all volumes.
     *
     * @return Volume[]
     */
    public function getAllVolumes(): array
    {
        return $this->service()->getAllVolumes()->all();
    }

    /**
     * Returns a volume by its ID.
     *
     * @param int $volumeId
     *
     * @return Volume|null
     */
    public function getVolumeById(int $volumeId): ?Volume
    {
        return $this->service()->getVolumeById($volumeId);
    }

    /**
     * @return Volume
     * @since 4.0.0
     */
    public function getTemporaryVolume(): Volume
    {
        return $this->service()->getTemporaryVolume();
    }

    /**
     * Get the user photo volume.
     *
     * @return Volume|null
     */
    public function getUserPhotoVolume(): ?Volume
    {
        return $this->service()->getUserPhotoVolume();
    }

    /**
     * Returns a volume by its UID.
     *
     * @param string $volumeUid
     *
     * @return Volume|null
     */
    public function getVolumeByUid(string $volumeUid): ?Volume
    {
        return $this->service()->getVolumeByUid($volumeUid);
    }

    /**
     * Returns a volume by its handle.
     *
     * @param string $handle
     *
     * @return Volume|null
     */
    public function getVolumeByHandle(string $handle): ?Volume
    {
        return $this->service()->getVolumeByHandle($handle);
    }

    /**
     * Returns the config for the given volume.
     *
     * @param Volume $volume
     *
     * @return array
     * @since 3.5.0
     * @deprecated in 4.0.0. Use [[Volume::getConfig()]] instead.
     */
    public function createVolumeConfig(Volume $volume): array
    {
        return $volume->getConfig();
    }

    /**
     * Creates or updates a volume.
     *
     * @param Volume $volume the volume to be saved.
     * @param bool $runValidation Whether the volume should be validated
     *
     * @return bool Whether the volume was saved successfully
     */
    public function saveVolume(Volume $volume, bool $runValidation = true): bool
    {
        return $this->service()->saveVolume($volume, $runValidation);
    }

    /**
     * Handle volume change
     *
     * @param ConfigEvent $event
     */
    public function handleChangedVolume(ConfigEvent $event): void
    {
        $this->service()->handleChangedVolume($event);
    }

    /**
     * Reorders asset volumes.
     *
     * @param array $volumeIds
     *
     * @return bool
     */
    public function reorderVolumes(array $volumeIds): bool
    {
        return $this->service()->reorderVolumes($volumeIds);
    }

    /**
     * Ensures a top level folder exists that matches the model.
     *
     * @param Volume $volume
     *
     * @return VolumeFolder
     * @deprecated in 4.5.0. [[Assets::getRootFolderByVolumeId()]] should be used instead.
     */
    public function ensureTopFolder(Volume $volume): VolumeFolder
    {
        $folder = Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id);
        if (!$folder) {
            throw new InvalidArgumentException(sprintf('Invalid volume passed to %s().', __METHOD__));
        }
        return $folder;
    }

    /**
     * Deletes an asset volume by its ID.
     *
     * @param int $volumeId
     *
     * @return bool
     */
    public function deleteVolumeById(int $volumeId): bool
    {
        return $this->service()->deleteVolumeById($volumeId);
    }

    /**
     * Deletes an asset volume.
     *
     * @param Volume $volume The volume to delete
     *
     * @return bool
     */
    public function deleteVolume(Volume $volume): bool
    {
        return $this->service()->deleteVolume($volume);
    }

    /**
     * Handle volume getting deleted
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedVolume(ConfigEvent $event): void
    {
        $this->service()->handleDeletedVolume($event);
    }

    /**
     * @deprecated in 4.0.5. Unused fields will be pruned automatically as field layouts are resaved.
     */
    public function pruneDeletedField(): void
    {
    }

    public static function registerEvents(): void
    {
        EventFacade::listen(SavingVolume::class, function(SavingVolume $event) {
            if (!Craft::$app->getVolumes()->hasEventHandlers(self::EVENT_BEFORE_SAVE_VOLUME)) {
                return;
            }

            Craft::$app->getVolumes()->trigger(self::EVENT_BEFORE_SAVE_VOLUME, new VolumeEvent([
                'volume' => $event->volume,
                'isNew' => $event->isNew,
            ]));
        });

        EventFacade::listen(VolumeSaved::class, function(VolumeSaved $event) {
            if (!Craft::$app->getVolumes()->hasEventHandlers(self::EVENT_AFTER_SAVE_VOLUME)) {
                return;
            }

            Craft::$app->getVolumes()->trigger(self::EVENT_AFTER_SAVE_VOLUME, new VolumeEvent([
                'volume' => $event->volume,
                'isNew' => $event->isNew,
            ]));
        });

        EventFacade::listen(DeletingVolume::class, function(DeletingVolume $event) {
            if (!Craft::$app->getVolumes()->hasEventHandlers(self::EVENT_BEFORE_DELETE_VOLUME)) {
                return;
            }

            Craft::$app->getVolumes()->trigger(self::EVENT_BEFORE_DELETE_VOLUME, new VolumeEvent([
                'volume' => $event->volume,
            ]));
        });

        EventFacade::listen(ApplyingVolumeDelete::class, function(ApplyingVolumeDelete $event) {
            if (!Craft::$app->getVolumes()->hasEventHandlers(self::EVENT_BEFORE_APPLY_VOLUME_DELETE)) {
                return;
            }

            Craft::$app->getVolumes()->trigger(self::EVENT_BEFORE_APPLY_VOLUME_DELETE, new VolumeEvent([
                'volume' => $event->volume,
            ]));
        });

        EventFacade::listen(VolumeDeleted::class, function(VolumeDeleted $event) {
            if (!Craft::$app->getVolumes()->hasEventHandlers(self::EVENT_AFTER_DELETE_VOLUME)) {
                return;
            }

            Craft::$app->getVolumes()->trigger(self::EVENT_AFTER_DELETE_VOLUME, new VolumeEvent([
                'volume' => $event->volume,
            ]));
        });
    }

    private function service(): VolumesService
    {
        return app(VolumesService::class);
    }
}
