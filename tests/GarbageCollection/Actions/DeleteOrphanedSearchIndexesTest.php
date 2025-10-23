<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedSearchIndexes;
use CraftCms\Cms\Support\Facades\Sites;

it('deletes orphaned data', function () {
    $element = Element::factory()->create();
    $field = Field::factory()->create();

    DB::table(Table::SEARCHINDEX)->insert(array_filter([
        'elementId' => $element->id,
        'attribute' => 'foo',
        'fieldId' => $field->id,
        'siteId' => Sites::getCurrentSite()->id,
        'keywords' => 'foo',
        'keywords_vector' => DB::connection()->getDriverName() === 'pgsql' ? 'foo' : null,
    ]));

    DB::table(Table::SEARCHINDEX)->insert(array_filter([
        'elementId' => 999,
        'attribute' => 'foo',
        'fieldId' => $field->id,
        'siteId' => Sites::getCurrentSite()->id,
        'keywords' => 'foo',
        'keywords_vector' => DB::connection()->getDriverName() === 'pgsql' ? 'foo' : null,
    ]));

    $originalCount = DB::table(Table::SEARCHINDEX)->count();

    app(DeleteOrphanedSearchIndexes::class)();

    expect(DB::table(Table::SEARCHINDEX)->count())->toBe($originalCount - 1);
});
