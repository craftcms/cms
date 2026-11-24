<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * @event FoldersDeleted The event that is triggered after folders are deleted.
 *
 * {@see $folderIds} includes the IDs of the deleted folders’ subfolders.
 *
 * @since 6.0.0
 */
class FoldersDeleted implements ShouldDispatchAfterCommit
{
    /** @param int[] $folderIds */
    public function __construct(
        public array $folderIds,
    ) {}
}
