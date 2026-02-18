<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Entries;
use CraftCms\Cms\Entry\Events\EntryMovedToSection;
use CraftCms\Cms\Entry\Events\MovingEntryToSection;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->entries = app(Entries::class);
});

it('can get an entry by id', function () {
    $entry = Entry::factory()->create();

    expect($this->entries->getEntryById($entry->id))->toBeInstanceOf(\CraftCms\Cms\Entry\Elements\Entry::class);
});

it('returns null when entry does not exist', function () {
    expect($this->entries->getEntryById(999))->toBeNull();
    expect($this->entries->getEntryById(0))->toBeNull();
});

it('can get an entry in a specific site', function () {
    $entry = Entry::factory()->create();

    $secondSite = Site::factory()->create();

    expect($this->entries->getEntryById($entry->id, Site::first()->id))->toBeInstanceOf(\CraftCms\Cms\Entry\Elements\Entry::class);
    expect($this->entries->getEntryById($entry->id, $secondSite->id))->toBeNull();
});

it('can get single entries by handle', function () {
    $singleSection = Section::factory()->create([
        'handle' => 'contact',
        'type' => SectionType::Single,
    ]);

    expect($this->entries->getSingleEntriesByHandle(['contact']))->toBeEmpty();

    Entry::factory()->create([
        'sectionId' => $singleSection->id,
    ]);

    $this->entries->refreshSingleEntries();

    expect($this->entries->getSingleEntriesByHandle(['contact']))->not()->toBeEmpty();
});

it('can move an entry to a different section', function () {
    Event::fake([
        MovingEntryToSection::class,
        EntryMovedToSection::class,
    ]);

    Event::listen(MovingEntryToSection::class, fn () => null);
    Event::listen(EntryMovedToSection::class, fn () => null);

    $entryType = EntryType::factory()->create();

    $section1 = Section::factory()->create([
        'type' => SectionType::Channel,
    ]);

    $section2 = Section::factory()->create([
        'type' => SectionType::Channel,
    ]);
    $section2->entryTypes()->attach($entryType, ['sortOrder' => 1]);

    $entry = Entry::factory()->create([
        'sectionId' => $section1->id,
        'typeId' => $entryType->id,
    ]);

    expect($this->entries->getEntryById($entry->id)->sectionId)->toBe($section1->id);

    $this->entries->moveEntryToSection(
        $this->entries->getEntryById($entry->id),
        Sections::getSectionById($section2->id),
    );

    expect($this->entries->getEntryById($entry->id)->sectionId)->toBe($section2->id);

    Event::assertDispatchedOnce(MovingEntryToSection::class);
    Event::assertDispatchedOnce(EntryMovedToSection::class);
});

it('cannot move if the entry is not saved', function () {
    $entry = Entry::factory()->create([
        'sectionId' => Section::factory()->create()->id,
    ]);
    $entry = $this->entries->getEntryById($entry->id);
    $entry->id = null;

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Attempting to move an unsaved element.');

    expect($this->entries->moveEntryToSection($entry, Sections::getAllSections()->first()));
});

it('cannot move a nested entry', function () {
    $entry = Entry::factory()->create([
        'sectionId' => Section::factory()->create()->id,
    ]);
    $entry = $this->entries->getEntryById($entry->id);
    $entry->primaryOwnerId = 1;

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Attempting to move a nested element.');

    expect($this->entries->moveEntryToSection($entry, Sections::getAllSections()->first()));
});
