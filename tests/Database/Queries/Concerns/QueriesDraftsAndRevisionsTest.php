<?php

use CraftCms\Cms\Element\Models\Draft;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;

test('drafts', function () {
    EntryModel::factory()->create();

    $entry = EntryModel::factory()->create();
    $entry->element->update([
        'draftId' => Draft::factory()->create([
            'canonicalId' => $entry->id,
            'provisional' => false,
        ])->id,
    ]);

    expect(entryQuery()->drafts()->count())->toBe(1);
    expect(entryQuery()->drafts(null)->count())->toBe(2);
    expect(entryQuery()->drafts(false)->count())->toBe(1);
});
