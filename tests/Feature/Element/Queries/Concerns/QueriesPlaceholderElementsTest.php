<?php

use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Support\Facades\Elements;

test('placeholder elements', function () {
    $entry = Entry::factory()->create();
    $entry->element->siteSettings()->first()->update([
        'title' => 'Old title',
    ]);

    $element = Elements::getElementById($entry->id);

    expect($element->title)->toBe('Old title');

    $element->title = 'New title';

    expect(entryQuery()->id($entry->id)->first()->title)->toBe('Old title');

    Elements::setPlaceholderElement($element);

    expect(entryQuery()->id($entry->id)->first()->title)->toBe('New title');
    expect(entryQuery()->id($entry->id)->ignorePlaceholders()->first()->title)->toBe('Old title');
});
