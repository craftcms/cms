<?php

use CraftCms\Cms\Entry\Models\Entry;

test('placeholder elements', function () {
    $entry = Entry::factory()->create();
    $entry->element->siteSettings()->first()->update([
        'title' => 'Old title',
    ]);

    $element = Craft::$app->getElements()->getElementById($entry->id);

    expect($element->title)->toBe('Old title');

    $element->title = 'New title';

    expect(entryQuery()->id($entry->id)->first()->title)->toBe('Old title');

    Craft::$app->getElements()->setPlaceholderElement($element);

    expect(entryQuery()->id($entry->id)->first()->title)->toBe('New title');
    expect(entryQuery()->id($entry->id)->ignorePlaceholders()->first()->title)->toBe('Old title');
});
