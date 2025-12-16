<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use Craft;

final class DeleteOrphanedSearchIndexJobs extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting orphaned search index jobs',
            function () {
                Craft::$app->getSearch()->deleteOrphanedIndexJobs();
            },
        );
    }
}
