<?php

use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Support\Facades\Elements;

test('overrides', function () {
    $entry = Entry::factory()->create();

    $element = Elements::getElementById($entry->id);

    $query = entryQuery()->id(999);

    $query->setResultOverride([$element]);

    expect($query->count())->toBe(1);
    expect($query->get()->count())->toBe(1);
    expect($query->pluck('id')->count())->toBe(1);
});
