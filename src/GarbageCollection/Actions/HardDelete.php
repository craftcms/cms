<?php

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\GarbageCollection\GarbageCollection;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Hard-deletes any rows in the given table(s) that are due for it.
 */
final class HardDelete extends GarbageCollectionAction
{
    /**
     * @param  string|string[]  $tables  The table(s) to delete rows from. They must have a `dateDeleted` column.
     */
    public function __construct(
        GarbageCollection $garbageCollection,
        GeneralConfig $generalConfig,
        private readonly array|string $tables,
    ) {
        parent::__construct($garbageCollection, $generalConfig);
    }

    public function __invoke(): void
    {
        if (! $this->shouldHardDelete()) {
            return;
        }

        foreach (Arr::wrap($this->tables) as $table) {
            $this->components->task(
                "deleting trashed rows in the `$table` table",
                function () use ($table) {
                    $this->hardDeleteQuery(DB::table($table))->delete();
                },
            );
        }
    }
}
