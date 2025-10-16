<?php

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

final class DeleteStaleSessions extends GarbageCollectionAction
{
    public function run(): void
    {
        if ($this->generalConfig->purgeStaleUserSessionDuration === 0) {
            return;
        }

        $this->components->task(
            'deleting stale user sessions',
            function () {
                DB::table(Table::SESSIONS)
                    ->where('dateUpdated', '<', now()->subSeconds($this->generalConfig->purgeStaleUserSessionDuration))
                    ->delete();
            },
        );
    }
}
