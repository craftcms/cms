<?php

use craft\elements\Entry;
use CraftCms\Cms\Database\Queries\ElementQuery;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use Illuminate\Support\Facades\DB;

function query(): ElementQuery
{
    return new ElementQuery(Entry::class);
}

it('queries enabled elements by default', function () {
    $element1 = Element::factory()->create([
        'enabled' => true,
    ]);

    Element::factory()->create(['enabled' => false]);

    $element3 = Element::factory()->create([
        'enabled' => true,
    ]);
    DB::table(Table::ELEMENTS_SITES)->where('elementId', $element3->id)->update([
        'enabled' => false,
    ]);

    expect(query()->count())->toBe(1);
    expect(query()->firstOrFail()->id)->toBe($element1->id);
});

it('can query archived and statuses', function () {
    $element1 = Element::factory()->create([
        'enabled' => true,
    ]);

    $element2 = Element::factory()->create([
        'enabled' => true,
        'archived' => true,
    ]);

    expect(query()->count())->toBe(1);
    expect(query()->first()->id)->toBe($element1->id);

    expect(query()->archived()->count())->toBe(1);
    expect(query()->archived()->first()->id)->toBe($element2->id);

    expect(query()->status([
        \craft\base\Element::STATUS_ENABLED,
        \craft\base\Element::STATUS_ARCHIVED,
    ])->count())->toBe(2);

    expect(query()->status([
        \craft\base\Element::STATUS_ARCHIVED,
    ])->count())->toBe(1);

    // Does not fail but doesn't apply parameters
    expect(query()->status(['not'])->count())->toBe(1);

    expect(query()->status(['not', \craft\base\Element::STATUS_ENABLED])->count())->toBe(0);
    expect(query()->status(['not', \craft\base\Element::STATUS_ARCHIVED])->count())->toBe(1);
});
