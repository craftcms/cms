<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::first()));

it('says when there are no entry types', function () {
    visitCp('settings/entry-types')
        ->assertSee('No entry types exist yet.');
});

it('creates an entry type and keeps editing it with the keyboard shortcut', function () {
    $page = visitCp('settings/entry-types')
        ->click('#cp-content-header >> role=link[name="New entry type"]')
        ->assertPathEndsWith('/settings/entry-types/new')
        ->type('input[name="name"]', 'My New Entry Type')
        ->assertValue('input[name="handle"]', 'myNewEntryType')
        ->keys('input[name="name"]', 'ControlOrMeta+s')
        ->assertSeeIn('#main h1', 'My New Entry Type');

    $entryType = EntryType::query()->where('handle', 'myNewEntryType')->firstOrFail();

    expect($entryType->name)->toBe('My New Entry Type');

    $page->assertPathEndsWith("/settings/entry-types/$entryType->id");
});

it('searches the entry types', function () {
    EntryType::factory()->create(['name' => 'Article', 'handle' => 'blogPost']);
    EntryType::factory()->create(['name' => 'Recipe', 'handle' => 'dish']);

    visitCp('settings/entry-types')
        ->assertSeeIn('#main table', 'Article')
        ->assertSeeIn('#main table', 'Recipe')
        ->type('input[name="search"]', 'Article')
        ->assertDontSeeIn('#main table', 'Recipe')
        ->assertSeeIn('#main table', 'blogPost')
        ->type('input[name="search"]', 'I dont exist')
        ->assertSee('No results for “I dont exist”.');
});

it('saves changes to an entry type', function () {
    $entryType = EntryType::factory()->create(['name' => 'Article', 'handle' => 'blogPost']);

    visitCp('settings/entry-types')
        ->click('#main table >> role=link[name="Article"]')
        ->assertPathEndsWith("/settings/entry-types/$entryType->id")
        ->type('input[name="name"]', 'Long Read')
        ->assertValue('input[name="handle"]', 'blogPost')
        ->click('#main >> role=button[name="Save"]')
        ->assertPathEndsWith('/settings/entry-types')
        ->assertSeeIn('#main table', 'Long Read')
        ->assertSeeIn('#main table', 'blogPost');

    expect($entryType->fresh()->name)->toBe('Long Read');
});

it('builds a field layout with a new tab', function () {
    Field::factory()->create(['name' => 'Body', 'handle' => 'body', 'type' => PlainText::class]);
    Field::factory()->create(['name' => 'Summary', 'handle' => 'summary', 'type' => PlainText::class]);
    Fields::refreshFields();

    $entryType = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([new EntryTitleField]))
        ->create(['name' => 'Article', 'handle' => 'article']);

    $page = visitCp("settings/entry-types/$entryType->id")
        ->click('#main .fld-tab >> role=button[name="Add"]')
        ->click('.fld-library .fld-field[data-attribute="body"]')
        ->click('.fld-library .fld-field[data-attribute="summary"]')
        ->assertPresent('#main .fld-tab .fld-field[data-attribute="summary"]')
        ->keys('.fld-library >> role=textbox[name="Search"]', 'Escape')
        ->assertMissing('.fld-library');

    $page->script('window.prompt = () => "Details"');

    $page->click('#main >> role=button[name="New tab"] >> nth=0')
        ->assertSeeIn('#main .fld-tab >> nth=1', 'Details')
        ->drag('#main .fld-tab .fld-field[data-attribute="summary"]', '#main .fld-tab >> nth=1 >> .fld-tabcontent')
        ->assertPresent('#main .fld-tab >> nth=1 >> .fld-field[data-attribute="summary"]')
        ->click('#main >> role=button[name="Save"]')
        ->assertPathEndsWith('/settings/entry-types');

    $layout = Fields::getLayoutById($entryType->fresh()->fieldLayoutId);

    expect(collect($layout->getTabs())->mapWithKeys(fn ($tab) => [
        $tab->name => collect($tab->getElements())->map(fn (BaseField $element) => $element->attribute())->all(),
    ])->all())->toBe([
        'Content' => ['title', 'body'],
        'Details' => ['summary'],
    ]);
});
