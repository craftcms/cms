<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\GarbageCollection\Actions\DeletePointlessChangeData;

it('deletes pointless change data', function (string $table, array $attributes) {
    $field = Field::factory()->create();
    $element = Element::factory()->create();
    $draftId = DB::table(Table::DRAFTS)->insertGetId([
        'name' => 'draft',
    ]);
    $element2 = Element::factory()->create();

    $draftElement = Element::factory()->create([
        'canonicalId' => $element->id,
        'draftId' => $draftId,
    ]);

    if (isset($attributes['fieldId'])) {
        $attributes['fieldId'] = $field->id;
    }

    /**
     * Valid, has an element with a draft
     */
    DB::table($table)->insert(array_merge($attributes, [
        'elementId' => $element->id,
        'siteId' => 1,
        'dateUpdated' => now(),
        'propagated' => true,
    ]));

    /**
     * Pointless, has an element without a draft
     */
    DB::table($table)->insert(array_merge($attributes, [
        'elementId' => $element2->id,
        'siteId' => 1,
        'dateUpdated' => now(),
        'propagated' => true,
    ]));

    $originalCount = DB::table($table)->count();

    app(DeletePointlessChangeData::class)();

    expect(DB::table($table)->count())->toBe($originalCount - 1);
})->with([
    [Table::CHANGEDATTRIBUTES, [
        'attribute' => 'foo',
    ]],
    [Table::CHANGEDFIELDS, [
        'fieldId' => 1,
    ]],
]);
