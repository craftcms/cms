<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\OperationTypes;

use CraftCms\Cms\Activity\Contracts\ActivityOperationTypeInterface;
use CraftCms\Cms\Activity\Data\ActivityOperation;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;

/**
 * Restoring an entry type, which restores its entries.
 *
 * @since 6.0.0
 */
class EntryTypeOperation implements ActivityOperationTypeInterface
{
    public static function for(int $entryTypeId, string $name): ActivityOperation
    {
        return new ActivityOperation(
            type: self::class,
            id: Str::uuid()->toString(),
            label: $name,
            data: ['entryTypeId' => $entryTypeId],
        );
    }

    public static function label(string $id, array $snapshot): ?string
    {
        return null;
    }

    public static function url(string $id, array $snapshot, User $viewer): ?string
    {
        return $viewer->admin
            ? route('craft.cp.settings.entry-types.edit', ['entryType' => $snapshot['entryTypeId']])
            : null;
    }
}
