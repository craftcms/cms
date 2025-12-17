<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Support\Facades\Users;

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
                Users::purgeExpiredPendingUsers();
            },
        );
    }
}
