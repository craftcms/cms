<?php

use CraftCms\Cms\Entry\Models\Entry as EntryModel;

test('search', function () {
    $entry1 = EntryModel::factory()->title('Foo')->indexed()->create();
    $entry2 = EntryModel::factory()->title('Bar')->indexed()->create();

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->search('Foo')->count())->toBe(1);
});

test('search with score', function () {
    $entry1 = EntryModel::factory()->title('Foo')->indexed()->create();
    $entry2 = EntryModel::factory()->title('Bar')->slug('Foo')->indexed()->create();

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
