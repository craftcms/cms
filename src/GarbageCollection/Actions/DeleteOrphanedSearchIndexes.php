<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Support\Facades\Search;

/**
 * @since 6.0.0
 */
class DeleteOrphanedSearchIndexes extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting orphaned search indexes',
            function () {
                Search::deleteOrphanedIndexes();
            },
        );
    }
}
