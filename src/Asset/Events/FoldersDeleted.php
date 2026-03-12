<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

/**
 * @event FoldersDeleted The event that is triggered after folders are deleted.
 */
class FoldersDeleted
{
    /** @param int[] $folderIds */
    public function __construct(
        public array $folderIds,
    ) {}
}
