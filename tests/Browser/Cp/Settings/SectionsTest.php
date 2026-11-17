<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::first()));

it('says when there are no sections', function () {
    visitCp('settings/sections')
        ->assertSee('No sections exist yet.');
});

it('creates a channel with a new entry type', function () {
    $page = visitCp('settings/sections')
        ->click('#cp-content-header >> role=link[name="New section"]')
        ->assertSeeIn('#main h1', 'Create a new section')
        ->type('input[name="name"]', 'My New Channel')
        ->assertValue('input[name="handle"]', 'myNewChannel')
        ->click('role=radio[name="Channel"]')
        ->click('#main >> role=button[name="Create"]');

    $page->assertVisible('role=dialog')
        ->type('role=dialog >> input[name="name"]', 'Channel Entry Type')
        ->assertValue('role=dialog >> input[name="handle"]', 'channelEntryType')
        ->click('role=dialog >> role=button[name="Save"]')
        ->assertSeeIn('role=group[name="Entry Types"] >> craft-chip', 'Channel Entry Type')
        ->click('#main >> role=button[name="Save"]')
        ->assertPathEndsWith('/settings/sections');

    $section = Section::query()->where('handle', 'myNewChannel')->firstOrFail();

    expect($section->name)->toBe('My New Channel')
        ->and($section->type)->toBe(SectionType::Channel)
        ->and($section->entryTypes->pluck('handle')->all())->toBe(['channelEntryType']);
});

it('saves changes to a section', function () {
    $section = Section::factory()
        ->withEntryTypes(EntryType::factory()->create())
        ->create(['name' => 'News', 'handle' => 'news']);

    visitCp("settings/sections/$section->id")
        ->type('input[name="name"]', 'Latest News')
        ->assertValue('input[name="handle"]', 'news')
        ->click('#main >> role=button[name="Save"]')
        ->assertPathEndsWith('/settings/sections')
        ->assertVisible('#main table >> role=link[name="Latest News"]');

    expect($section->fresh()->name)->toBe('Latest News');
});

it('restores the full-width index after saving a section', function () {
    Section::factory()
        ->withEntryTypes(EntryType::factory()->create())
        ->create(['name' => 'Daily News', 'handle' => 'dailyNews']);

    $tableEdge = 'document.querySelector("#main table").getBoundingClientRect().right';
    $page = visitCp('settings/sections');
    $fullWidthEdge = $page->script($tableEdge);

    $page->click('#main table >> role=link[name="Daily News"]')
        ->assertScript("document.querySelector('#main input[name=\"name\"]').getBoundingClientRect().right < $fullWidthEdge")
        ->click('#main >> role=button[name="Save"]')
        ->assertPathEndsWith('/settings/sections')
        ->assertScript($tableEdge, $fullWidthEdge);
});
