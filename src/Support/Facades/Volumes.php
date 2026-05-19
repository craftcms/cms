<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Illuminate\Support\Collection getAllVolumeIds()
 * @method static \Illuminate\Support\Collection getViewableVolumeIds()
 * @method static \Illuminate\Support\Collection getViewableVolumes()
 * @method static int getTotalVolumes()
 * @method static int getTotalViewableVolumes()
 * @method static \Illuminate\Support\Collection getAllVolumes()
 * @method static \CraftCms\Cms\Asset\Data\Volume|null getVolumeById(int $volumeId)
 * @method static \CraftCms\Cms\Asset\Data\Volume|null getVolumeByUid(string $volumeUid)
 * @method static \CraftCms\Cms\Asset\Data\Volume|null getVolumeByHandle(string $handle)
 * @method static \CraftCms\Cms\Asset\Data\Volume getTemporaryVolume()
 * @method static \CraftCms\Cms\Asset\Data\Volume|null getUserPhotoVolume()
 * @method static bool saveVolume(\CraftCms\Cms\Asset\Data\Volume $volume, bool $runValidation = true)
 * @method static void handleChangedVolume(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static bool reorderVolumes(int[] $volumeIds)
 * @method static bool deleteVolumeById(int $volumeId)
 * @method static bool deleteVolume(\CraftCms\Cms\Asset\Data\Volume $volume)
 * @method static void handleDeletedVolume(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static void reset()
 *
 * @see \CraftCms\Cms\Asset\Volumes
 */
class Volumes extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Asset\Volumes::class;
    }
}
