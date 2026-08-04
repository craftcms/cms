<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

class DeleteOrphanedStructureElements extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting orphaned structure elements',
            function () {
                DB::table(Table::STRUCTUREELEMENTS, 'se')
                    ->leftJoin(new Alias(Table::ELEMENTS, 'e'), 'e.id', 'se.elementId')
                    ->whereNotNull('se.elementId')
                    ->whereNull('e.id')
                    ->pluck('se.id')
                    ->chunk($this->garbageCollection::CHUNK_SIZE)
                    ->each(function ($idsChunk) {
                        DB::table(Table::STRUCTUREELEMENTS)
                            ->whereIn('id', $idsChunk)
                            ->delete();
                    });
            },
        );
    }
}
