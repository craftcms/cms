<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

/** @internal */
readonly class CascadeDeleteDraftsAndRevisionsAction
{
    public function handle(int $canonicalId, bool $delete = true): void
    {
        foreach (['draftId' => Table::DRAFTS, 'revisionId' => Table::REVISIONS] as $fk => $table) {
            DB::table(new Alias(Table::ELEMENTS, 'e'))
                ->whereIn(
                    "e.$fk",
                    DB::table(new Alias($table, 't'))
                        ->select('t.id')
                        ->where('t.canonicalId', $canonicalId),
                )
                ->update([
                    'dateDeleted' => $delete ? now() : null,
                ]);
        }
    }
}
