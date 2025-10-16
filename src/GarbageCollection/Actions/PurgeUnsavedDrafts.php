<?php

namespace CraftCms\Cms\GarbageCollection\Actions;

final class PurgeUnsavedDrafts extends GarbageCollectionAction
{
    public function run(): void
    {
        if ($this->generalConfig->purgeUnsavedDraftsDuration === 0) {
            return;
        }

        $this->components->task(
            'purging unsaved drafts that have gone stale',
            function () {
                \Craft::$app->getDrafts()->purgeUnsavedDrafts();
            },
        );
    }
}
