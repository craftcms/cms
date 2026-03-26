<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

class DeleteStaleBulkOpData extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting stale bulk operation data',
            function () {
                foreach ([Table::BULKOPEVENTS, Table::ELEMENTS_BULKOPS] as $table) {
                    DB::table($table)
                        ->where('timestamp', '<', now()->subWeeks(2))
                        ->delete();
                }
            },
        );
    }
}
