<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\InvalidateElementCaches;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Entries;
use CraftCms\Cms\Entry\Events\EntryMovedToSection;
use CraftCms\Cms\Entry\Events\MovingEntryToSection;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->entries = app(Entries::class);
});

it('can get an entry by id', function () {
    $entry = Entry::factory()->create();

    expect($this->entries->getEntryById($entry->id))->toBeInstanceOf(EntryElement::class);
});

it('returns null when entry does not exist', function () {
    expect($this->entries->getEntryById(999))->toBeNull();
    expect($this->entries->getEntryById(0))->toBeNull();
});

it('can get an entry in a specific site', function () {
    $entry = Entry::factory()->create();

    $secondSite = Site::factory()->create();

    expect($this->entries->getEntryById($entry->id, Site::first()->id))->toBeInstanceOf(EntryElement::class);
    expect($this->entries->getEntryById($entry->id, $secondSite->id))->toBeNull();
});

it('can get single entries by handle', function () {
    $singleSection = Section::factory()->create([
        'handle' => 'contact',
        'type' => SectionType::Single,
    ]);

    expect($this->entries->getSingleEntriesByHandle(['contact']))->toBeEmpty();

    Entry::factory()->forSection($singleSection)->create();

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

    $section2 = Section::factory()->withEntryTypes($entryType)->create([
        'type' => SectionType::Channel,
    ]);

    $entry = Entry::factory()->forSection($section1)->forEntryType($entryType)->create();

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
    $section = Section::factory()->create();
    $entry = Entry::factory()->forSection($section)->create();
    $entry = $this->entries->getEntryById($entry->id);
    $entry->id = null;

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Attempting to move an unsaved element.');

    expect($this->entries->moveEntryToSection($entry, Sections::getAllSections()->first()));
});

it('cannot move a nested entry', function () {
    $section = Section::factory()->create();
    $entry = Entry::factory()->forSection($section)->create();
    $entry = $this->entries->getEntryById($entry->id);
    $entry->primaryOwnerId = 1;

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Attempting to move a nested element.');

    expect($this->entries->moveEntryToSection($entry, Sections::getAllSections()->first()));
});

it('can reassign entries to a new author', function () {
    Event::fake([InvalidateElementCaches::class]);

    $oldAuthor = User::factory()->create();
    $newAuthor = User::factory()->create();
    $unchangedAuthor = User::factory()->create();

    $reassignedEntry = Entry::factory()
        ->hasAttached($oldAuthor, ['sortOrder' => 1], 'authors')
        ->create();

    $unchangedEntry = Entry::factory()
        ->hasAttached($unchangedAuthor, ['sortOrder' => 1], 'authors')
        ->create();

    expect($this->entries->reassignEntries($oldAuthor->id, $newAuthor->id))->toBe(1);

    expect(DB::table(Table::ENTRIES_AUTHORS)
        ->where('entryId', $reassignedEntry->id)
        ->pluck('authorId')
        ->all())->toBe([$newAuthor->id]);

    expect(DB::table(Table::ENTRIES_AUTHORS)
        ->where('entryId', $unchangedEntry->id)
        ->pluck('authorId')
        ->all())->toBe([$unchangedAuthor->id]);

    Event::assertDispatched(fn (InvalidateElementCaches $event): bool => $event->tags === ['element::'.EntryElement::class]);
});

it('does not reassign entries that already have the new author', function () {
    $oldAuthor = User::factory()->create();
    $newAuthor = User::factory()->create();

    $entry = Entry::factory()
        ->hasAttached($oldAuthor, ['sortOrder' => 1], 'authors')
        ->hasAttached($newAuthor, ['sortOrder' => 2], 'authors')
        ->create();

    expect($this->entries->reassignEntries($oldAuthor->id, $newAuthor->id))->toBe(0);

    expect(DB::table(Table::ENTRIES_AUTHORS)
        ->where('entryId', $entry->id)
        ->orderBy('sortOrder')
        ->pluck('authorId')
        ->all())->toBe([$oldAuthor->id, $newAuthor->id]);
});

it('can reassign entries from multiple old authors', function () {
    $oldAuthorA = User::factory()->create();
    $oldAuthorB = User::factory()->create();
    $newAuthor = User::factory()->create();

    $entryA = Entry::factory()
        ->hasAttached($oldAuthorA, ['sortOrder' => 1], 'authors')
        ->create();

    $entryB = Entry::factory()
        ->hasAttached($oldAuthorB, ['sortOrder' => 1], 'authors')
        ->create();

    expect($this->entries->reassignEntries([$oldAuthorA->id, $oldAuthorB->id], $newAuthor->id))->toBe(2);

    expect(DB::table(Table::ENTRIES_AUTHORS)
        ->whereIn('entryId', [$entryA->id, $entryB->id])
        ->pluck('authorId')
        ->all())->toBe([$newAuthor->id, $newAuthor->id]);
});
