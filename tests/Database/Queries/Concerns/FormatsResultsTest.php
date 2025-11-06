<?php

use CraftCms\Cms\Entry\Models\Entry;
use Illuminate\Support\Collection;

test('inReverse', function () {
    [$element1, $element2, $element3] = Entry::factory(3)->create([
        'dateCreated' => now(),
    ]);

    expect(entryQuery()->orderBy('id')->pluck('id')->all())->toBe([$element1->id, $element2->id, $element3->id]);
    expect(entryQuery()->orderBy('id')->inReverse()->pluck('id')->all())->toBe([$element3->id, $element2->id, $element1->id]);
});

test('asArray', function () {
    $element = Entry::factory()->create();

    expect(entryQuery()->get())->toBeInstanceOf(Collection::class);
    expect(entryQuery()->asArray()->get())->toBeArray();

    expect(entryQuery()->pluck('id'))->toBeInstanceOf(Collection::class);
    expect(entryQuery()->asArray()->pluck('id'))->toBeArray();

    expect(entryQuery()->findMany([$element->id]))->toBeInstanceOf(Collection::class);
    expect(entryQuery()->asArray()->findMany([$element->id]))->toBeArray();
});

test('fixedOrder', function () {
    [$element1, $element2, $element3] = Entry::factory(3)->create();

    expect(entryQuery()->id([$element2->id, $element3->id, $element1->id])->fixedOrder()->pluck('id')->all())->toBe([$element2->id, $element3->id, $element1->id]);
    expect(entryQuery()->id([$element3->id, $element1->id, $element2->id])->fixedOrder()->pluck('id')->all())->toBe([$element3->id, $element1->id, $element2->id]);
    expect(entryQuery()->id(implode(', ', [$element3->id, $element1->id, $element2->id]))->fixedOrder()->pluck('id')->all())->toBe([$element3->id, $element1->id, $element2->id]);
});
