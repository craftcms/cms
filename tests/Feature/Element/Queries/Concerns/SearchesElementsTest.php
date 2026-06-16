<?php

use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Support\Facades\Search;

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

test('asset search with score', function () {
    $asset = AssetModel::factory()->create([
        'filename' => 'foo-searchable.jpg',
    ]);

    Search::indexElementAttributes(AssetElement::find()->id($asset->id)->one());

    $results = assetQuery()->search('foo-searchable')->orderByDesc('score')->get();

    expect($results)->toHaveCount(1);
    expect($results[0]->id)->toBe($asset->id);
});
