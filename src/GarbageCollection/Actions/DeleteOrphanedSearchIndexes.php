<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use Craft;

final class DeleteOrphanedSearchIndexes extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting orphaned search indexes',
            function () {
                Craft::$app->getSearch()->deleteOrphanedIndexes();
            },
        );
    }
}
