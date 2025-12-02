<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\GarbageCollection\Actions\HardDeleteStructures;
use CraftCms\Cms\Structure\Models\Structure;
use Illuminate\Support\Facades\DB;

it('hard deletes soft deleted structures', function () {
    $element = Element::factory()->create();

    // Active
    $s1 = Structure::factory()->create(['dateDeleted' => null]);
    createStructureElement($element->id, $s1->id);

    // Recently deleted
    $s2 = Structure::factory()->create(['dateDeleted' => now()]);
    createStructureElement($element->id, $s2->id);

    // Old deleted
    $s3 = Structure::factory()->create(['dateDeleted' => now()->subSeconds(Cms::config()->softDeleteDuration + 1)]);
    createStructureElement($element->id, $s3->id);

    $currentCount = DB::table(Table::STRUCTURES)->count();

    resolve(HardDeleteStructures::class)();

    expect(DB::table(Table::STRUCTURES)->count())->toBe($currentCount - 1);
});

function createStructureElement(int $elementId, int $structureId): void
{
    DB::table(Table::STRUCTUREELEMENTS)->insert([
        'elementId' => $elementId,
        'structureId' => $structureId,
        'lft' => 0,
        'rgt' => 0,
        'level' => 0,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);
}
