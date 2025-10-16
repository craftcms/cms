<?php

namespace CraftCms\Cms\GarbageCollection\Actions;

final class DeleteOrphanedSearchIndexes extends GarbageCollectionAction
{
    public function run(): void
    {
        $this->components->task(
            'deleting orphaned search indexes',
            function () {
                \Craft::$app->getSearch()->deleteOrphanedIndexes();
            },
        );
    }
}
