<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Entries;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Entries\MoveEntryToSectionController;
use CraftCms\Cms\Section\Models\Section;
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
