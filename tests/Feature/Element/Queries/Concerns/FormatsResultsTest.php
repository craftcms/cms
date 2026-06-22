<?php

use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Support\Facades\Deprecator;
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
    expect(entryQuery()->asArray()->all())->toBeArray();
    expect(entryQuery()->asArray()->first())->toBeArray();
    expect(entryQuery()->asArray()->sole())->toBeArray();
});

test('fixedOrder', function () {
    [$element1, $element2, $element3] = Entry::factory(3)->create();

    expect(entryQuery()->id([$element2->id, $element3->id, $element1->id])->fixedOrder()->pluck('id')->all())->toBe([$element2->id, $element3->id, $element1->id]);
    expect(entryQuery()->id([$element3->id, $element1->id, $element2->id])->fixedOrder()->pluck('id')->all())->toBe([$element3->id, $element1->id, $element2->id]);
    expect(entryQuery()->id(implode(', ', [$element3->id, $element1->id, $element2->id]))->fixedOrder()->pluck('id')->all())->toBe([$element3->id, $element1->id, $element2->id]);
});

test('it applies a default order when no orderBy is specified', function () {
    $query = entryQuery();
    $query->applyBeforeQueryCallbacks();

    expect(
        collect($query->getQuery()->orders)
            ->where('column', 'entries.postDate')
            ->where('direction', 'desc')
            ->first()
    )->not()->toBeNull();

    expect(
        collect($query->getQuery()->orders)
            ->where('column', 'entries.id')
            ->where('direction', 'desc')
            ->first()
    )->not()->toBeNull();

    $query = entryQuery()->orderBy('slug');
    $query->applyBeforeQueryCallbacks();

    expect(
        collect($query->getQuery()->orders)
            ->where('column', 'entries.postDate')
            ->where('direction', 'desc')
            ->first()
    )->toBeNull();

    expect(
        collect($query->getQuery()->orders)
            ->where('column', 'elements.id')
            ->where('direction', 'desc')
            ->first()
    )->toBeNull();
});

it('orders by revisions when revisions are requested', function () {
    $query = entryQuery()->revisions();
    $query->applyBeforeQueryCallbacks();

    expect(
        collect($query->getQuery()->orders)
            ->where('column', 'num')
            ->where('direction', 'desc')
            ->first()
    )->not()->toBeNull();
});

it('adds a sort on structureelements.lft when the element has structures', function () {
    $query = entryQuery();
    $query->withStructure();
    $query->applyBeforeQueryCallbacks();

    expect(
        collect($query->getQuery()->orders)
            ->where('column', 'structureelements.lft')
            ->where('direction', 'asc')
            ->first()
    )->not()->toBeNull();
});

it('maps concrete element table order columns', function (Closure $query, string $attribute, string $column) {
    $query = $query()->orderBy($attribute);
    $query->applyBeforeQueryCallbacks();

    expect(
        collect($query->getQuery()->orders)
            ->where('column', $column)
            ->where('direction', 'asc')
            ->first()
    )->not()->toBeNull();
})->with([
    'asset filename' => [assetQuery(...), 'filename', 'assets.filename'],
    'asset modified date' => [assetQuery(...), 'dateModified', 'assets.dateModified'],
    'entry expiry date' => [entryQuery(...), 'expiryDate', 'entries.expiryDate'],
    'user full name' => [userQuery(...), 'fullName', 'users.fullName'],
    'user last login date' => [userQuery(...), 'lastLoginDate', 'users.lastLoginDate'],
    'address country code' => [fn () => new AddressQuery, 'countryCode', 'addresses.countryCode'],
]);

it('parses string order columns with directions', function () {
    $query = entryQuery()->orderBy('dateUpdated DESC, title ASC');
    $query->applyBeforeQueryCallbacks();

    expect($query->getQuery()->orders)
        ->toContain(['column' => 'elements.dateUpdated', 'direction' => 'desc'])
        ->toContain(['column' => 'elements_sites.title', 'direction' => 'asc']);
});

it('parses string order columns with directions from find criteria', function () {
    $entry = Entry::factory()->create();

    expect(CraftCms\Cms\Entry\Elements\Entry::findOne([
        'sectionId' => $entry->sectionId,
        'orderBy' => 'dateUpdated DESC',
    ]))->not()->toBeNull();
});

it('parses string order columns with directions from query config', function () {
    $query = entryQuery(['orderBy' => 'dateUpdated DESC']);
    $query->applyBeforeQueryCallbacks();

    expect($query->getQuery()->orders)
        ->toContain(['column' => 'elements.dateUpdated', 'direction' => 'desc']);
});

it('logs a deprecation for legacy string order columns', function () {
    entryQuery()->orderBy('dateUpdated DESC');

    expect(collect(array_keys(Deprecator::getRequestLogs()))
        ->contains(fn (string $key) => str_starts_with($key, EntryQuery::class.'::orderBy(string)-')))
        ->toBeTrue();
});
