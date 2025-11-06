<?php

use craft\elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;

it('can run basic queries', function () {
    expect(entryQuery()->all())->toBeEmpty();

    $elements = EntryModel::factory(5)->create();

    expect(entryQuery()->all())->toHaveCount(5);
    expect(entryQuery()->get())->toHaveCount(5);
    expect(entryQuery()->one())->toBeInstanceOf(Entry::class);
    expect(entryQuery()->first())->toBeInstanceOf(Entry::class);
    expect(entryQuery()->firstOrFail())->toBeInstanceOf(Entry::class);
    expect(entryQuery()->limit(3)->get())->toHaveCount(3);
    expect(entryQuery()->find($elements[0]->id))->toBeInstanceOf(Entry::class);
    expect(entryQuery()->where('elements.id', $elements[0]->id))->sole()->toBeInstanceOf(Entry::class);
    expect(entryQuery()->offset(4)->limit(10)->get())->toHaveCount(1);

    $this->expectException(MultipleRecordsFoundException::class);
    entryQuery()->sole();

    $this->expectException(ModelNotFoundException::class);
    entryQuery()->findOrFail(999);
});

test('id', function () {
    [$element1, $element2] = EntryModel::factory(3)->create();

    expect(entryQuery()->id($element1->id)->get())->toHaveCount(1);
    expect(entryQuery()->id([$element1->id, $element2->id])->get())->toHaveCount(2);
    expect(entryQuery()->id(implode(',', [$element1->id, $element2->id]))->get())->toHaveCount(2);
    expect(entryQuery()->id(implode(', ', [$element1->id, $element2->id]))->get())->toHaveCount(2);
});

test('uid', function () {
    [$element1, $element2] = EntryModel::factory(3)->create();

    expect(entryQuery()->uid($element1->element->uid)->get())->toHaveCount(1);
    expect(entryQuery()->uid([$element1->element->uid, $element2->element->uid])->get())->toHaveCount(2);
    expect(entryQuery()->uid(implode(',', [$element1->element->uid, $element2->element->uid]))->get())->toHaveCount(2);
    expect(entryQuery()->uid(implode(', ', [$element1->element->uid, $element2->element->uid]))->get())->toHaveCount(2);
});

test('trashed', function () {
    EntryModel::factory(2)->create();
    EntryModel::factory(2)->trashed()->create();

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->trashed(true)->count())->toBe(2);
    expect(entryQuery()->trashed(null)->count())->toBe(4);
});
