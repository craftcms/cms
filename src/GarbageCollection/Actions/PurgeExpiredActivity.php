<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurgeExpiredActivity extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        if ($this->generalConfig->activityRetentionDuration === 0) {
            return;
        }

        $this->components->task(
            'purging expired activity',
            function () {
                DB::table(Table::ACTIVITYEVENTS)
                    ->select('id')
                    ->whereNull('rootEventId')
                    ->where('occurredAt', '<', now()->subSeconds($this->generalConfig->activityRetentionDuration))
                    ->orderBy('id')
                    ->chunkById(
                        $this->garbageCollection::CHUNK_SIZE,
                        fn (Collection $events) => DB::table(Table::ACTIVITYEVENTS)
                            ->whereIn('id', $events->pluck('id'))
                            ->delete(),
                    );
            },
        );
    }
}
