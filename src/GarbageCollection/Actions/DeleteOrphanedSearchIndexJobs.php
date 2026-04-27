<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Support\Facades\Search;

class DeleteOrphanedSearchIndexJobs extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting orphaned search index jobs',
            function () {
                Search::deleteOrphanedIndexJobs();
            },
        );
    }
}
