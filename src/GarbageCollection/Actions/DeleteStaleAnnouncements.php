<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

class DeleteStaleAnnouncements extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting stale feature announcements',
            function () {
                DB::table(Table::ANNOUNCEMENTS)
                    ->where('dateRead', '<', now()->subDays(7))
                    ->delete();
            },
        );
    }
}
