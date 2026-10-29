<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

/**
 * @since 6.0.0
 */
class DeleteOrphanedRelations extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting orphaned relations',
            function () {
                DB::table(Table::RELATIONS, 'r')
                    ->leftJoin(Table::ELEMENTS.' as e', 'e.id', 'r.targetId')
                    ->whereNull('e.id')
                    ->pluck('r.id')
                    ->chunk($this->garbageCollection::CHUNK_SIZE)
                    ->each(function ($idsChunk) {
                        DB::table(Table::RELATIONS)
                            ->whereIn('id', $idsChunk)
                            ->delete();
                    });
            },
        );
    }
}
