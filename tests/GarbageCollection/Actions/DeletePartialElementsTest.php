<?php

use craft\elements\Entry;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\GarbageCollection\Actions\DeletePartialElements;

it('deletes elements that are missing data in the element extension table', function () {
    $element = Element::factory([
        'type' => Entry::class,
    ])->create();

    $originalCount = DB::table(Table::ELEMENTS)->count();

    expect(DB::table(Table::ELEMENTS)->find($element->id))->not()->toBeNull();

    app(DeletePartialElements::class, [
        'elementType' => Entry::class,
        'table' => Table::ENTRIES,
    ])();

    expect(DB::table(Table::ELEMENTS)->count())->toBe($originalCount - 1);
    expect(DB::table(Table::ELEMENTS)->find($element->id))->toBeNull();
});
