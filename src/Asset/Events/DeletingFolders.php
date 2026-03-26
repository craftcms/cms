<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event DeletingFolders The event that is triggered before folders are deleted.
 */
class DeletingFolders
{
    use ValidatableEvent;

    /** @param int[] $folderIds */
    public function __construct(
        public array $folderIds,
    ) {}
}
