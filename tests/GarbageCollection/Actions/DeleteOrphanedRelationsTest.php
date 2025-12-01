<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedRelations;
use Illuminate\Support\Facades\DB;

it('deletes orphaned data', function () {
    $element = Element::factory()->create();
    $field = Field::factory()->create();

    // Valid, not deleted
    DB::table(Table::RELATIONS)->insert([
        'targetId' => $element->id,
        'sourceId' => 1,
        'fieldId' => $field->id,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    // Invalid, deleted
    DB::table(Table::RELATIONS)->insert([
        'targetId' => 999,
        'fieldId' => $field->id,
        'sourceId' => 1,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    expect(DB::table(Table::RELATIONS)->count())->toBe(2);

    app(DeleteOrphanedRelations::class)();

    expect(DB::table(Table::RELATIONS)->count())->toBe(1);
});
