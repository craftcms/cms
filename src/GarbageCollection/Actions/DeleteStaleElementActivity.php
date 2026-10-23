<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Query;
use Illuminate\Support\Facades\DB;

/**
 * @since 6.0.0
 */
class DeleteStaleElementActivity extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting stale element activity records',
            function () {
                DB::table(Table::ELEMENTACTIVITY)
                    ->where('timestamp', '<', Query::prepareDateForDb(now()->subMinute()))
                    ->delete();
            },
        );
    }
}
