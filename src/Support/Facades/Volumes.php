<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Illuminate\Support\Collection<int, int> getAllVolumeIds()
 * @method static \Illuminate\Support\Collection<int, int> getViewableVolumeIds()
 * @method static \Illuminate\Support\Collection<int, \CraftCms\Cms\Asset\Data\Volume> getViewableVolumes()
 * @method static int getTotalVolumes()
 * @method static int getTotalViewableVolumes()
 * @method static \Illuminate\Support\Collection<int, \CraftCms\Cms\Asset\Data\Volume> getAllVolumes()
 * @method static \CraftCms\Cms\Asset\Data\Volume|null getVolumeById(int $volumeId)
 * @method static \CraftCms\Cms\Asset\Data\Volume|null getVolumeByUid(string $volumeUid)
 * @method static \CraftCms\Cms\Asset\Data\Volume|null getVolumeByHandle(string $handle)
 * @method static \CraftCms\Cms\Asset\Data\Volume getTemporaryVolume()
 * @method static \CraftCms\Cms\Asset\Data\Volume|null getUserPhotoVolume()
 * @method static bool saveVolume(\CraftCms\Cms\Asset\Data\Volume $volume, bool $runValidation = true)
 * @method static void handleChangedVolume(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static bool reorderVolumes(array $volumeIds)
 * @method static bool deleteVolumeById(int $volumeId)
 * @method static bool deleteVolume(\CraftCms\Cms\Asset\Data\Volume $volume)
 * @method static void handleDeletedVolume(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 *
 * @see \CraftCms\Cms\Asset\Volumes
 */
final class Volumes extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Asset\Volumes::class;
    }
}
