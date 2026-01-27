<?php

use CraftCms\Cms\Entry\Models\Entry;

it('can query entries by entry types', function () {
    $entry1 = Entry::factory()->create();
    $entry2 = Entry::factory()->create();

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->typeId($entry1->typeId)->count())->toBe(1);
    expect(entryQuery()->typeId($entry2->typeId)->count())->toBe(1);
    expect(entryQuery()->typeId([$entry1->typeId, $entry2->typeId])->count())->toBe(2);
    expect(entryQuery()->typeId(implode(', ', [$entry1->typeId, $entry2->typeId]))->count())->toBe(2);

    expect(entryQuery()->type('*')->count())->toBe(2);
    expect(entryQuery()->type($entry1->entryType->handle)->count())->toBe(1);
    expect(entryQuery()->type($entry2->entryType->handle)->count())->toBe(1);
    expect(entryQuery()->type([$entry1->entryType->handle, $entry2->entryType->handle])->count())->toBe(2);
    expect(entryQuery()->type(implode(', ', [$entry1->entryType->handle, $entry2->entryType->handle]))->count())->toBe(2);

    expect(entryQuery()->type('not '.$entry1->entryType->handle)->count())->toBe(1);
    expect(entryQuery()->type('not '.$entry2->entryType->handle)->count())->toBe(1);
});
