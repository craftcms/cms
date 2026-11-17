<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Activity;

use CraftCms\Cms\Activity\Contracts\ActivityOperationTypeInterface;
use CraftCms\Cms\Activity\Data\ActivityOperation;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\User\Elements\User;

/**
 * Publishing a content release.
 *
 * @since 6.0.0
 */
class ReleaseOperation implements ActivityOperationTypeInterface
{
    public static function for(Release $release): ActivityOperation
    {
        return new ActivityOperation(
            type: self::class,
            id: "release:$release->id",
            label: $release->name,
            data: ['releaseId' => $release->id],
        );
    }

    public static function label(string $id, array $snapshot): ?string
    {
        return null;
    }

    public static function url(string $id, array $snapshot, User $viewer): ?string
    {
        return Edition::isAtLeast(Edition::Pro)
            ? route('craft.cp.releases.show', $snapshot['releaseId'])
            : null;
    }
}
