<?php

use craft\base\Element;
use CraftCms\Cms\Entry\Models\Entry;

it('queries enabled elements by default', function () {
    $element1 = Entry::factory()->enabled()->create();

    Entry::factory()->disabled()->create();

    $element3 = Entry::factory()->enabled()->create();
    // Disabled in site
    $element3->element->siteSettings->first()->update(['enabled' => false]);

    expect(entryQuery()->count())->toBe(1);
    expect(entryQuery()->firstOrFail()->id)->toBe($element1->id);
});

it('can query archived and statuses', function () {
    $element1 = Entry::factory()->create();
    $element2 = Entry::factory()->archived()->create();

    expect(entryQuery()->count())->toBe(1);
    expect(entryQuery()->first()->id)->toBe($element1->id);

    expect(entryQuery()->archived()->count())->toBe(1);
    expect(entryQuery()->archived()->first()->id)->toBe($element2->id);

    expect(entryQuery()->status([
        Element::STATUS_ENABLED,
        Element::STATUS_ARCHIVED,
    ])->count())->toBe(2);

    expect(entryQuery()->status([
        Element::STATUS_ARCHIVED,
    ])->count())->toBe(1);

    // Does not fail but doesn't apply parameters
    expect(entryQuery()->status(['not'])->count())->toBe(1);

    expect(entryQuery()->status(['not', Element::STATUS_ENABLED])->count())->toBe(0);
    expect(entryQuery()->status(['not', Element::STATUS_ARCHIVED])->count())->toBe(1);
});
