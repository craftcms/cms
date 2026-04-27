<?php

use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Support\Facades\Elements;

dataset('falsy-ref-values', [
    0,
    '0',
]);

it('can query entries by ref', function () {
    $entry = Entry::factory()->create();

    $element = Elements::getElementById($entry->id);

    expect(entryQuery()->count())->toBe(1);
    expect(entryQuery()->ref($entry->slug)->count())->toBe(1);
    expect(entryQuery()->ref("{$element->section->handle}/{$element->slug}")->count())->toBe(1);
});

it('treats falsy refs as explicit filters', function (mixed $ref) {
    $matchingEntry = Entry::factory()->slug('0')->create();
    Entry::factory()->slug('other-entry')->create();

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->ref($ref)->count())->toBe(1);
    expect(entryQuery()->ref($ref)->one()?->id)->toBe($matchingEntry->id);
})->with('falsy-ref-values');
