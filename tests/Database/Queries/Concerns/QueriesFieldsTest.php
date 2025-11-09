<?php

use CraftCms\Cms\Entry\Models\Entry as EntryModel;

test('id', function () {
    [$element1, $element2] = EntryModel::factory(3)->create();

    expect(entryQuery()->id('not '.$element1->id.','.$element2->id)->get())->toHaveCount(1);
    expect(entryQuery()->id($element1->id)->get())->toHaveCount(1);
    expect(entryQuery()->id([$element1->id, $element2->id])->get())->toHaveCount(2);
    expect(entryQuery()->id(implode(',', [$element1->id, $element2->id]))->get())->toHaveCount(2);
    expect(entryQuery()->id(implode(', ', [$element1->id, $element2->id]))->get())->toHaveCount(2);

    expect(entryQuery()->id("> {$element1->id}")->get())->toHaveCount(2);
    expect(entryQuery()->id(">= {$element1->id}")->get())->toHaveCount(3);
    expect(entryQuery()->id("and >={$element1->id}, >{$element2->id}")->get())->toHaveCount(1);
});

test('uid', function () {
    [$element1, $element2] = EntryModel::factory(3)->create();

    expect(entryQuery()->uid($element1->element->uid)->get())->toHaveCount(1);
    expect(entryQuery()->uid('not '.$element1->element->uid)->get())->toHaveCount(2);
    expect(entryQuery()->uid([$element1->element->uid, $element2->element->uid])->get())->toHaveCount(2);
    expect(entryQuery()->uid(implode(',', [$element1->element->uid, $element2->element->uid]))->get())->toHaveCount(2);
    expect(entryQuery()->uid(implode(', ', [$element1->element->uid, $element2->element->uid]))->get())->toHaveCount(2);
});

test('siteSettingsId', function () {
    [$element1, $element2] = EntryModel::factory(2)->create();

    expect(entryQuery()->siteSettingsId($element1->element->siteSettings->first()->id)->get())->toHaveCount(1);
    expect(entryQuery()->siteSettingsId($element1->element->siteSettings->first()->id)->first()->id)->toBe($element1->id);
    expect(entryQuery()->siteSettingsId([$element1->element->siteSettings->first()->id, $element2->element->siteSettings->first()->id])->get())->toHaveCount(2);
});

test('dateCreated', function () {
    $entry1 = EntryModel::factory()->create([
        'dateCreated' => now()->subDay(),
    ]);

    $entry2 = EntryModel::factory()->create([
        'dateCreated' => now()->subDays(2),
    ]);

    expect(entryQuery()->dateCreated(['and', '<= yesterday'])->count())->toBe(1);
});
