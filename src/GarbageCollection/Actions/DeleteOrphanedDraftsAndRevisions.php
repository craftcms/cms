<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

final class DeleteOrphanedDraftsAndRevisions extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting orphaned drafts and revisions',
            function () {
                $tables = [
                    'draftId' => Table::DRAFTS,
                    'revisionId' => Table::REVISIONS,
                ];

                foreach ($tables as $fk => $table) {
                    DB::table($table, 't')
                        ->leftJoin(new Alias(Table::ELEMENTS, 'e'), "e.$fk", 't.id')
                        ->whereNull('e.id')
                        ->pluck('t.id')
                        ->chunk($this->garbageCollection::CHUNK_SIZE)
                        ->each(function (Collection $idsChunk) use ($table) {
                            DB::table($table)
                                ->whereIn('id', $idsChunk)
                                ->delete();
                        });
                }
            },
        );
    }
}
