<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedDraftsAndRevisions;

it('deletes orphaned data', function (string $table, string $fk, array $data) {
    $tableId = DB::table($table)->insertGetId($data);

    expect(DB::table($table)->count())->toBe(1);

    /**
     * If the draft or revision is referenced, it won't get deleted
     */
    DB::table(Table::ELEMENTS)->update([
        $fk => $tableId,
    ]);

    app(DeleteOrphanedDraftsAndRevisions::class)();
    expect(DB::table($table)->count())->toBe(1);

    /**
     * Once the reference is gone it will be deleted
     */
    DB::table(Table::ELEMENTS)->update([
        $fk => null,
    ]);

    app(DeleteOrphanedDraftsAndRevisions::class)();
    expect(DB::table($table)->count())->toBe(0);
})->with([
    [Table::DRAFTS, 'draftId', [
        'name' => 'draft',
    ]],
    [Table::REVISIONS, 'revisionId', [
        'canonicalId' => 1,
        'num' => fake()->numberBetween(1, 100),
    ]],
]);
