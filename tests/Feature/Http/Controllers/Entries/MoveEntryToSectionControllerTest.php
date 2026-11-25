<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Entries;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Entries\MoveEntryToSectionController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

it('rejects an incompatible bulk move before moving any entries', function () {
    actingAs(User::findOne());

    $entryTypeA = EntryType::factory()->create();
    $entryTypeB = EntryType::factory()->create();
    $sourceSection = Section::factory()->withEntryTypes($entryTypeA, $entryTypeB)->create();
    $targetSection = Section::factory()->withEntryTypes($entryTypeA)->create();
    Section::factory()->withEntryTypes($entryTypeB)->create();
    $entryA = Entry::factory()->forSection($sourceSection)->forEntryType($entryTypeA)->create();
    $entryB = Entry::factory()->forSection($sourceSection)->forEntryType($entryTypeB)->create();

    postJson(action([MoveEntryToSectionController::class, 'move']), [
        'entryIds' => [$entryA->id, $entryB->id],
        'sectionId' => $targetSection->id,
    ])
        ->assertBadRequest()
        ->assertJsonPath('message', 'Not all entries have a type supported by the target section.');

    $entries = app(Entries::class);

    expect($entries->getEntryById($entryA->id)->sectionId)->toBe($sourceSection->id)
        ->and($entries->getEntryById($entryB->id)->sectionId)->toBe($sourceSection->id);
});

it('moves entries to the section the control panel posts as a string', function () {
    actingAs(User::findOne());

    $entryType = EntryType::factory()->create();
    $sourceSection = Section::factory()->withEntryTypes($entryType)->create();
    $targetSection = Section::factory()->withEntryTypes($entryType)->create();
    $entry = Entry::factory()->forSection($sourceSection)->forEntryType($entryType)->create();

    postJson(action([MoveEntryToSectionController::class, 'move']), [
        'entryIds' => [(string) $entry->id],
        'sectionId' => (string) $targetSection->id,
    ])->assertOk();

    expect(app(Entries::class)->getEntryById($entry->id)->sectionId)->toBe($targetSection->id);
});

it('returns the IDs of a section’s entries for moving them all', function () {
    actingAs(User::findOne());

    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    $otherSection = Section::factory()->withEntryTypes($entryType)->create();
    $entries = Entry::factory()->forSection($section)->forEntryType($entryType)->count(2)->create();
    Entry::factory()->forSection($otherSection)->forEntryType($entryType)->create();

    $entryIds = postJson(action([MoveEntryToSectionController::class, 'sectionEntryIds']), [
        'sectionId' => $section->id,
    ])->assertOk()->json('entryIds');

    expect($entryIds)->toEqualCanonicalizing($entries->pluck('id')->all());
});

it('lists the sections every selected entry could move to', function () {
    actingAs(User::findOne());

    $entryType = EntryType::factory()->create();
    $otherType = EntryType::factory()->create();
    $sourceSection = Section::factory()->withEntryTypes($entryType)->create();
    $targetSection = Section::factory()->withEntryTypes($entryType)->create(['name' => 'Target']);
    Section::factory()->withEntryTypes($otherType)->create();
    $entry = Entry::factory()->forSection($sourceSection)->forEntryType($entryType)->create();

    postJson(action([MoveEntryToSectionController::class, 'showModal']), [
        'entryIds' => [$entry->id],
        'siteId' => Sites::getPrimarySite()->id,
        'currentSectionUid' => $sourceSection->uid,
    ])
        ->assertOk()
        ->assertExactJson([
            'sections' => [
                ['id' => $targetSection->id, 'label' => 'Target'],
            ],
        ]);
});
