<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\ElementCachesInvalidated;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Entries;
use CraftCms\Cms\Entry\Events\EntryMovedToSection;
use CraftCms\Cms\Entry\Events\EntryMovingToSection;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Records which `entries_authors` write statements (delete/insert) run during $callback.
 *
 * @return string[]
 */
function captureEntriesAuthorsCommands(Closure $callback): array
{
    $commands = [];

    DB::listen(function (QueryExecuted $query) use (&$commands) {
        $sql = strtolower(ltrim($query->sql));
        if (! str_contains($sql, 'entries_authors')) {
            return;
        }

        if (str_starts_with($sql, 'delete')) {
            $commands[] = 'delete';
        } elseif (str_starts_with($sql, 'insert')) {
            $commands[] = 'insert';
        }
    });

    $callback();

    return $commands;
}

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
        EntryMovingToSection::class,
        EntryMovedToSection::class,
    ]);

    Event::listen(EntryMovingToSection::class, fn () => null);
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

    Event::assertDispatchedOnce(EntryMovingToSection::class);
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

it('cannot move an entry to a section that does not support its type', function () {
    $entryType = EntryType::factory()->create();
    $otherEntryType = EntryType::factory()->create();
    $sourceSection = Section::factory()->withEntryTypes($entryType)->create();
    $targetSection = Section::factory()->withEntryTypes($otherEntryType)->create();
    $entry = Entry::factory()->forSection($sourceSection)->forEntryType($entryType)->create();

    expect(fn () => $this->entries->moveEntryToSection(
        $this->entries->getEntryById($entry->id),
        Sections::getSectionById($targetSection->id),
    ))->toThrow(Exception::class, 'Entry type is not supported by the target section.');

    expect($this->entries->getEntryById($entry->id)->sectionId)->toBe($sourceSection->id);
});

it('can reassign entries to a new author', function () {
    Event::fake([ElementCachesInvalidated::class]);

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

    Event::assertDispatched(fn (ElementCachesInvalidated $event): bool => $event->tags === ['element::'.EntryElement::class]);
});

it('cannot reassign entries to the same author', function () {
    $author = User::factory()->create();

    expect(fn () => $this->entries->reassignEntries($author->id, $author->id))
        ->toThrow(InvalidArgumentException::class, 'The new author must be different from the old author.');
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

it('does not delete missing author rows when saving an entry’s authors for the first time', function () {
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create([
        'type' => SectionType::Channel,
    ]);
    $author = User::factory()->create();

    $entry = Entry::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement();
    $entry->setAuthorIds([$author->id]);

    $commands = captureEntriesAuthorsCommands(fn () => Elements::saveElement($entry));

    // No existing rows to delete yet, so the save shouldn’t issue a delete (which would otherwise
    // take a needless gap lock on the primary index and risk deadlocking against other transactions
    // inserting authors for their own new entries).
    expect($commands)->not->toContain('delete');
    expect($commands)->toContain('insert');
    expect(DB::table(Table::ENTRIES_AUTHORS)
        ->where('entryId', $entry->id)
        ->pluck('authorId')
        ->all())->toBe([$author->id]);
});

it('deletes existing author rows when an entry’s authors change', function () {
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create([
        'type' => SectionType::Channel,
    ]);
    $oldAuthor = User::factory()->create();
    $newAuthor = User::factory()->create();

    $entry = Entry::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement();
    $entry->setAuthorIds([$oldAuthor->id]);
    Elements::saveElement($entry);

    $entry->setAuthorIds([$newAuthor->id]);

    $commands = captureEntriesAuthorsCommands(fn () => Elements::saveElement($entry));

    // The old author row does exist this time, so the existing rows must still be removed.
    expect($commands)->toContain('delete');
    expect($commands)->toContain('insert');
    expect(DB::table(Table::ENTRIES_AUTHORS)
        ->where('entryId', $entry->id)
        ->pluck('authorId')
        ->all())->toBe([$newAuthor->id]);
});
