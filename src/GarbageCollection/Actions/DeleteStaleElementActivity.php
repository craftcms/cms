<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

final class DeleteStaleElementActivity extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting stale element activity records',
            function () {
                DB::table(Table::ELEMENTACTIVITY)
                    ->where('timestamp', '<', now()->subMinute())
                    ->delete();
            },
        );
    }
}
