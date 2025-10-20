<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedStructureElements;
use CraftCms\Cms\Support\Str;

it('deletes orphaned data', function () {
    $element = Element::factory()->create();

    $structureId = DB::table(Table::STRUCTURES)->insertGetId([
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid()->toString(),
    ]);

    DB::table(Table::STRUCTUREELEMENTS)->insert([
        'elementId' => $element->id,
        'structureId' => $structureId,
        'lft' => 0,
        'rgt' => 0,
        'level' => 0,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    DB::table(Table::STRUCTUREELEMENTS)->insert([
        'elementId' => 999,
        'structureId' => $structureId,
        'lft' => 0,
        'rgt' => 0,
        'level' => 0,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    $currentCount = DB::table(Table::STRUCTUREELEMENTS)->count();

    app(DeleteOrphanedStructureElements::class)();

    expect(DB::table(Table::STRUCTUREELEMENTS)->count())->toBe($currentCount - 1);
});
