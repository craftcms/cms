<?php

use craft\elements\Entry;
use CraftCms\Cms\Database\Queries\ElementQuery;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use Illuminate\Support\Facades\Date as Date;

function query(): ElementQuery {
    return new ElementQuery(Entry::class);
}

it('can run basic queries', function () {
    expect(query()->all())->toBeEmpty();

    Element::factory(5)->create();

    expect(query()->all())->toHaveCount(5);
    expect(query()->get())->toHaveCount(5);
    expect(query()->one())->toBeInstanceOf(Entry::class);
    expect(query()->first())->toBeInstanceOf(Entry::class);
    expect(query()->limit(3)->get())->toHaveCount(3);
    expect(query()->offset(4)->limit(10)->get())->toHaveCount(1);
});

test('id', function () {
    [$element1, $element2] = Element::factory(3)->create();

    expect(query()->id($element1->id)->get())->toHaveCount(1);
    expect(query()->id([$element1->id, $element2->id])->get())->toHaveCount(2);
    expect(query()->id(implode(',', [$element1->id, $element2->id]))->get())->toHaveCount(2);
    expect(query()->id(implode(', ', [$element1->id, $element2->id]))->get())->toHaveCount(2);
});

test('uid', function () {
    [$element1, $element2] = Element::factory(3)->create();

    expect(query()->uid($element1->uid)->get())->toHaveCount(1);
    expect(query()->uid([$element1->uid, $element2->uid])->get())->toHaveCount(2);
    expect(query()->uid(implode(',', [$element1->uid, $element2->uid]))->get())->toHaveCount(2);
    expect(query()->uid(implode(', ', [$element1->uid, $element2->uid]))->get())->toHaveCount(2);
});

test('trashed', function () {
    Element::factory(2)->create();
    Element::factory(2)->trashed()->create();

    expect(query()->count())->toBe(2);
    expect(query()->trashed(true)->count())->toBe(2);
    expect(query()->trashed(null)->count())->toBe(4);
});

test('dateCreated', function () {
    $timezone = app()->getTimezone();

    Date::setTestNow(Date::now($timezone)->startOfDay());

    Element::factory()->create([
        'dateCreated' => Date::now()->subDays(2),
    ]);

    Element::factory()->create([
        'dateCreated' => Date::now()->subDay(),
    ]);

    Element::factory()->create([
        'dateCreated' => Date::now(),
    ]);

    expect(query()->count())->toBe(3);
    expect(query()->dateCreated('>= ' . Date::now()->subDay()->toIso8601String())->count())->toBe(2);
    expect(query()->dateCreated('> ' . Date::now()->subDay()->toIso8601String())->count())->toBe(1);
});

test('reverse', function () {
    [$element1, $element2, $element3] = Element::factory(3)->create([
        'dateCreated' => now(),
    ]);

    expect(query()->pluck('id')->all())->toBe([$element3->id, $element2->id, $element1->id]);
    expect(query()->inReverse()->pluck('id')->all())->toBe([$element1->id, $element2->id, $element3->id]);
});
