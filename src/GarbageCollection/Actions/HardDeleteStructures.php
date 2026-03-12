<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;
use Tpetry\QueryExpressions\Language\Alias;

class HardDeleteStructures extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        // get IDs of structures that can be deleted;
        // those are the ones for which the elements don't have any revisions
        $structuresTable = Table::STRUCTURES;
        $structureElementsTable = Table::STRUCTUREELEMENTS;
        $elementsTable = Table::ELEMENTS;
        $revisionsTable = Table::REVISIONS;

        $structureIds = $this->hardDeleteQuery(DB::table($structuresTable, 's'), 's')
            ->leftJoin(new Alias($structureElementsTable, 'se'), 's.id', 'se.structureId')
            ->leftJoin(new Alias($elementsTable, 'e'), 'e.id', 'se.elementId')
            ->leftJoin(new Alias($revisionsTable, 'r'), 'r.canonicalId', '=', new Coalesce(['e.canonicalId', 'e.id']))
            ->whereNotNull('se.elementId')
            ->whereNull('r.canonicalId')
            ->distinct()
            ->pluck('s.id');

        if ($structureIds->isEmpty()) {
            return;
        }

        // and now perform the actual deletion based on those IDs
        $this->hardDeleteQuery(DB::table($structuresTable, 's'), 's')
            ->whereIn('s.id', $structureIds)
            ->delete();
    }
}
