<?php

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

final class DeletePointlessChangeData extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        foreach ([
            Table::CHANGEDATTRIBUTES,
            Table::CHANGEDFIELDS,
        ] as $table) {
            $this->components->task(
                sprintf('deleting pointless rows in the %s table', $table),
                function () use ($table) {
                    // fetch any rows in the table for canonical elements that don't have any drafts
                    DB::table($table, 't')
                        ->join(new Alias(Table::ELEMENTS, 'e'), 'e.id', 't.elementId')
                        ->leftJoin(new Alias(Table::ELEMENTS, 'd'), function (JoinClause $join) {
                            $join->whereColumn('d.canonicalId', 'e.id')
                                ->whereNotNull('d.draftId');
                        })
                        ->whereNull('e.canonicalId')
                        ->whereNull('d.id')
                        ->groupBy('t.elementId')
                        ->select('t.elementId')
                        ->cursor()
                        ->chunk(100)
                        ->each(function ($chunk) use ($table) {
                            DB::table($table)
                                ->whereIn('elementId', $chunk->pluck('elementId'))
                                ->delete();
                        });
                }
            );
        }
    }
}
