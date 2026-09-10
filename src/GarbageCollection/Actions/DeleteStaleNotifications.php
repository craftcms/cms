<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Cp\Notifications\CpNotification;
use Illuminate\Notifications\DatabaseNotification;

class DeleteStaleNotifications extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting stale notifications',
            fn () => DatabaseNotification::query()
                ->where('type', CpNotification::TYPE)
                ->where('read_at', '<', now()->subDays(7))
                ->delete(),
        );
    }
}
