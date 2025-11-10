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
