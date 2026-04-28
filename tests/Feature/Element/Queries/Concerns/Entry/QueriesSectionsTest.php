<?php

use CraftCms\Cms\Entry\Models\Entry;

it('can query entries by section', function () {
    $entry1 = Entry::factory()->create();
    $entry2 = Entry::factory()->create();

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->sectionId($entry1->sectionId)->count())->toBe(1);
    expect(entryQuery()->sectionId($entry2->sectionId)->count())->toBe(1);
    expect(entryQuery()->sectionId([$entry1->sectionId, $entry2->sectionId])->count())->toBe(2);
    expect(entryQuery()->sectionId(implode(', ', [$entry1->sectionId, $entry2->sectionId]))->count())->toBe(2);

    expect(entryQuery()->section('*')->count())->toBe(2);
    expect(entryQuery()->section($entry1->section->handle)->count())->toBe(1);
    expect(entryQuery()->section($entry2->section->handle)->count())->toBe(1);
    expect(entryQuery()->section([$entry1->section->handle, $entry2->section->handle])->count())->toBe(2);
    expect(entryQuery()->section(implode(', ', [$entry1->section->handle, $entry2->section->handle]))->count())->toBe(2);

    expect(entryQuery()->section('not '.$entry1->section->handle)->count())->toBe(1);
    expect(entryQuery()->section('not '.$entry2->section->handle)->count())->toBe(1);
});
