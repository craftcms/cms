<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::first());

    $this->entryType = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([new EntryTitleField]))
        ->create(['name' => 'Article', 'handle' => 'article']);

    $this->section = Section::factory()
        ->withEntryTypes($this->entryType)
        ->create(['name' => 'Articles', 'handle' => 'articles', 'enableVersioning' => true]);
});

it('creates an entry', function () {
    visitCp('content/entries/articles')
        ->click('#main >> role=link[name="New Entry"]')
        ->assertSeeIn('#main h1', 'Create a new entry')
        ->type('input[name="title"]', 'Hello world')
        ->click('#main >> role=button[name="Create entry"]')
        ->assertSee('Entry created.')
        ->assertVisible('#main tbody >> role=link[name="Hello world"]');

    $entry = EntryElement::find()->section('articles')->title('Hello world')->one();

    expect($entry)->not->toBeNull()
        ->and($entry->slug)->toBe('hello-world')
        ->and($entry->getIsDraft())->toBeFalse();
});

it('saves changes to an entry', function () {
    $entry = Entry::factory()->forSection($this->section)->forEntryType($this->entryType)
        ->createElement(['title' => 'First draft']);

    visitCp('content/entries/articles')
        ->click('#main tbody >> role=link[name="First draft"]')
        ->assertValue('input[name="title"]', 'First draft')
        ->type('input[name="title"]', 'Final copy')
        ->click('#main >> role=button[name="Save"]')
        ->assertSee('Entry saved.')
        ->assertVisible('#main tbody >> role=link[name="Final copy"]');

    expect(EntryElement::find()->id($entry->id)->one()->title)->toBe('Final copy');
});

it('autosaves unsaved changes without touching the live entry', function () {
    $entry = Entry::factory()->forSection($this->section)->forEntryType($this->entryType)
        ->createElement(['title' => 'First draft']);

    visitCp("content/entries/articles/$entry->id")
        ->type('input[name="title"]', 'Work in progress')
        ->assertSee('Showing your unsaved changes.')
        ->refresh()
        ->assertValue('input[name="title"]', 'Work in progress');

    expect(EntryElement::find()->id($entry->id)->one()->title)->toBe('First draft')
        ->and(EntryElement::find()->provisionalDrafts()->draftOf($entry->id)->one()?->title)->toBe('Work in progress');
});

it('discards unsaved changes', function () {
    $entry = Entry::factory()->forSection($this->section)->forEntryType($this->entryType)
        ->createElement(['title' => 'First draft']);

    visitCp("content/entries/articles/$entry->id")
        ->type('input[name="title"]', 'Work in progress')
        ->click('#main >> role=button[name="Discard changes"]')
        ->assertValue('input[name="title"]', 'First draft')
        ->assertDontSee('Showing your unsaved changes.');

    expect(EntryElement::find()->provisionalDrafts()->draftOf($entry->id)->exists())->toBeFalse();
});

it('applies a draft to the live entry', function () {
    $entry = Entry::factory()->forSection($this->section)->forEntryType($this->entryType)
        ->createElement(['title' => 'First draft']);

    $page = visitCp("content/entries/articles/$entry->id")
        ->click('#main >> role=button[name="Create a draft"]')
        ->assertQueryStringHas('draftId')
        ->type('input[name="title"]', 'Second thoughts')
        ->click('#main >> role=button[name="Save draft"]')
        ->assertSee('Draft saved.');

    expect(EntryElement::find()->id($entry->id)->one()->title)->toBe('First draft')
        ->and(EntryElement::find()->drafts()->draftOf($entry->id)->one()?->title)->toBe('Second thoughts');

    $page->click('#main >> role=button[name="Apply draft"]')
        ->assertQueryStringMissing('draftId');

    expect(EntryElement::find()->id($entry->id)->one()->title)->toBe('Second thoughts')
        ->and(EntryElement::find()->drafts()->draftOf($entry->id)->exists())->toBeFalse();
});

it('reverts an entry to an earlier revision', function () {
    $entry = Entry::factory()->forSection($this->section)->forEntryType($this->entryType)
        ->createElement(['title' => 'Original']);
    $entry->title = 'Rewritten';
    Elements::saveElement($entry);

    visitCp("content/entries/articles/$entry->id")
        ->click('role=button[name="Versions"]')
        ->click('role=link[name="Revision 1"]')
        ->assertSee('You’re viewing a revision.')
        ->assertSeeIn('#main h1', 'Original')
        ->click('#main >> role=button[name="Revert content from this revision"]')
        ->assertQueryStringMissing('revisionId');

    expect(EntryElement::find()->id($entry->id)->one()->title)->toBe('Original');
});
