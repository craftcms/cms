<?php

namespace CraftCms\Cms\GarbageCollection\Actions;

final class PurgePendingUsers extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        if ($this->generalConfig->purgePendingUsersDuration === 0) {
            return;
        }

        $this->components->task(
            'purging pending users with stale activation codes',
            function () {
                \Craft::$app->getUsers()->purgeExpiredPendingUsers();
            },
        );
    }
}
