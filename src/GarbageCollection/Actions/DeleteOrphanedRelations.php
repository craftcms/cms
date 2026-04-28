<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

class DeleteOrphanedRelations extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting orphaned relations',
            function () {
                DB::table(Table::RELATIONS, 'r')
                    ->leftJoin(new Alias(Table::ELEMENTS, 'e'), 'e.id', 'r.targetId')
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
