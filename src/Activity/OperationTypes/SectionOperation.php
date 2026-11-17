<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\OperationTypes;

use CraftCms\Cms\Activity\Contracts\ActivityOperationTypeInterface;
use CraftCms\Cms\Activity\Data\ActivityOperation;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;

/**
 * Restoring a section, which restores its entries.
 *
 * @since 6.0.0
 */
class SectionOperation implements ActivityOperationTypeInterface
{
    public static function for(int $sectionId, string $name): ActivityOperation
    {
        return new ActivityOperation(
            type: self::class,
            id: Str::uuid()->toString(),
            label: $name,
            data: ['sectionId' => $sectionId],
        );
    }

    public static function label(string $id, array $snapshot): ?string
    {
        return null;
    }

    public static function url(string $id, array $snapshot, User $viewer): ?string
    {
        return $viewer->admin
            ? route('craft.cp.settings.sections.edit', ['section' => $snapshot['sectionId']])
            : null;
    }
}
