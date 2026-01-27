<?php

use CraftCms\Cms\Entry\Models\Entry as EntryModel;

test('search', function () {
    $entry1 = EntryModel::factory()->create();
    $entry1->element->siteSettings->first()->update([
        'title' => 'Foo',
    ]);

    $entry2 = EntryModel::factory()->create();
    $entry2->element->siteSettings->first()->update([
        'title' => 'Bar',
    ]);

    $element1 = Craft::$app->getElements()->getElementById($entry1->id);
    $element2 = Craft::$app->getElements()->getElementById($entry2->id);

    Craft::$app->getSearch()->indexElementAttributes($element1);
    Craft::$app->getSearch()->indexElementAttributes($element2);

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->search('Foo')->count())->toBe(1);
});

test('search with score', function () {
    $entry1 = EntryModel::factory()->create();
    $entry1->element->siteSettings->first()->update([
        'title' => 'Foo',
        'content' => '',
    ]);

    $entry2 = EntryModel::factory()->create();
    $entry2->element->siteSettings->first()->update([
        'title' => 'Bar',
        'slug' => 'Foo',
    ]);

    $element1 = Craft::$app->getElements()->getElementById($entry1->id);
    $element2 = Craft::$app->getElements()->getElementById($entry2->id);

    Craft::$app->getSearch()->indexElementAttributes($element1);
    Craft::$app->getSearch()->indexElementAttributes($element2);

    expect(entryQuery()->orderBy('score')->count())->toBe(2);
    expect(entryQuery()->search('Foo')->orderBy('score')->count())->toBe(2);

    $results = entryQuery()->search('Foo')->orderBy('score')->get();

    expect($results[0]->id)->toBe($entry2->id);
    expect($results[1]->id)->toBe($entry1->id);

    $results = entryQuery()->search('Foo')->orderByDesc('score')->get();

    expect($results[0]->id)->toBe($entry1->id);
    expect($results[1]->id)->toBe($entry2->id);

    $results = entryQuery()->search('Foo')->orderBy('score')->inReverse()->get();

    expect($results[0]->id)->toBe($entry1->id);
    expect($results[1]->id)->toBe($entry2->id);
});
