<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\OperationTypes;

use CraftCms\Cms\Activity\Contracts\ActivityOperationTypeInterface;
use CraftCms\Cms\Activity\Data\ActivityOperation;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;

/**
 * Deleting or restoring a volume, or deleting folders from it.
 *
 * @since 6.0.0
 */
class VolumeOperation implements ActivityOperationTypeInterface
{
    public static function for(int $volumeId, string $label): ActivityOperation
    {
        return new ActivityOperation(
            type: self::class,
            id: Str::uuid()->toString(),
            label: $label,
            data: ['volumeId' => $volumeId],
        );
    }

    public static function label(string $id, array $snapshot): ?string
    {
        return null;
    }

    public static function url(string $id, array $snapshot, User $viewer): ?string
    {
        return $viewer->admin
            ? route('craft.cp.settings.assets.volumes.edit', ['volumeId' => $snapshot['volumeId']])
            : null;
    }
}
