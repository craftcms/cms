<?php

use CraftCms\Cms\Entry\Models\Entry;

it('can query entries by ref', function () {
    $entry = Entry::factory()->create();

    $element = Craft::$app->getElements()->getElementById($entry->id);

    expect(entryQuery()->count())->toBe(1);
    expect(entryQuery()->ref($entry->slug)->count())->toBe(1);
    expect(entryQuery()->ref("{$element->section->handle}/{$element->slug}")->count())->toBe(1);
});
