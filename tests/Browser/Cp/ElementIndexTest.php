<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::first());

    $type = EntryType::factory()->create(['name' => 'Article', 'handle' => 'article']);
    $section = Section::factory()->withEntryTypes($type)->create(['name' => 'Articles', 'handle' => 'articles']);

    // Newest first, so post date order differs from title order.
    foreach (['Bravo', 'Alpha', 'Charlie'] as $daysAgo => $title) {
        Entry::factory()->forSection($section)->forEntryType($type)->indexed()
            ->createElement(['title' => $title, 'postDate' => now()->subDays($daysAgo)]);
    }
});

it('lists the newest entries first', function () {
    visitCp('content/entries/articles')
        ->assertVisible('#main tbody tr:nth-child(1) >> role=link[name="Bravo"]')
        ->assertVisible('#main tbody tr:nth-child(2) >> role=link[name="Alpha"]')
        ->assertVisible('#main tbody tr:nth-child(3) >> role=link[name="Charlie"]');
});

it('sorts by a column from its header', function () {
    $page = visitCp('content/entries/articles')
        ->click('#main thead >> role=button[name="Entry"]')
        ->assertVisible('#main tbody tr:nth-child(1) >> role=link[name="Alpha"]')
        ->assertVisible('#main tbody tr:nth-child(3) >> role=link[name="Charlie"]');

    $page->click('#main thead >> role=button[name="Entry"]')
        ->assertVisible('#main tbody tr:nth-child(1) >> role=link[name="Charlie"]')
        ->assertVisible('#main tbody tr:nth-child(3) >> role=link[name="Alpha"]');
});

it('keeps focus on the column header button after sorting', function () {
    visitCp('content/entries/articles')
        ->click('#main thead >> role=button[name="Post Date"]')
        ->assertVisible('#main tbody tr:nth-child(1) >> role=link[name="Charlie"]')
        ->assertScript('document.activeElement.textContent.trim()', 'Post Date');
});

it('adds a column from the view menu', function () {
    visitCp('content/entries/articles')
        ->assertMissing('#main thead >> role=button[name="ID"]')
        ->click('#main >> role=button[name="View"]')
        ->click('role=button[name="Add item"]')
        ->click('role=button[name="ID"]')
        ->click('role=button[name="Close"]')
        ->assertVisible('#main thead >> role=button[name="ID"]');
});

it('searches the entries', function () {
    visitCp('content/entries/articles')
        ->type('#main input[name="search"]', 'Alpha')
        ->assertQueryStringHas('search', 'Alpha')
        ->assertDontSeeIn('#main tbody', 'Bravo')
        ->assertVisible('#main tbody >> role=link[name="Alpha"]')
        ->assertDontSeeIn('#main tbody', 'Charlie');
});

it('disables the selected entries', function () {
    visitCp('content/entries/articles')
        ->click('#main >> role=checkbox[name="Select Alpha"]')
        ->click('#main >> role=checkbox[name="Select Bravo"]')
        ->click('#main >> role=button[name="Set Status"]')
        ->click('role=button[name="Disabled"]')
        ->assertMissing('#main >> role=button[name="Clear selection"]')
        ->assertSeeIn('#main tbody tr:has-text("Alpha")', 'Disabled');

    expect(EntryElement::find()->section('articles')->status(null)->collect()->mapWithKeys(fn ($entry) => [$entry->title => $entry->enabled])->sortKeys()->all())
        ->toBe(['Alpha' => false, 'Bravo' => false, 'Charlie' => true]);
});

it('deletes an entry and restores it from the trash', function () {
    $page = visitCp('content/entries/articles');
    $page->script('window.confirm = () => true');

    $page->click('#main >> role=checkbox[name="Select Alpha"]')
        ->click('#main >> role=button[name="Actions"] >> nth=-1')
        ->click('role=button[name="Delete"]')
        ->assertMissing('#main tbody >> role=link[name="Alpha"]')
        ->assertVisible('#main tbody >> role=link[name="Bravo"]');

    expect(EntryElement::find()->title('Alpha')->trashed()->exists())->toBeTrue();

    visitCp('content/entries/articles?status=trashed')
        ->assertVisible('#main tbody >> role=checkbox[name="Select Alpha"]')
        ->assertDontSeeIn('#main tbody', 'Bravo')
        ->click('#main >> role=checkbox[name="Select Alpha"]')
        ->click('#main >> role=button[name="Actions"] >> nth=-1')
        ->click('role=button[name="Restore"]')
        ->assertMissing('#main tbody >> role=checkbox[name="Select Alpha"]');

    expect(EntryElement::find()->title('Alpha')->trashed()->exists())->toBeFalse()
        ->and(EntryElement::find()->title('Alpha')->exists())->toBeTrue();
});

it('filters the entries by status', function () {
    $charlie = EntryElement::find()->title('Charlie')->one();
    $charlie->enabled = false;
    Elements::saveElement($charlie);

    visitCp('content/entries/articles')
        ->click('#main >> role=button[name="Status All"]')
        ->click('role=option[name="Disabled"]')
        ->assertQueryStringHas('status', 'disabled')
        ->assertDontSeeIn('#main tbody', 'Alpha')
        ->assertVisible('#main tbody >> role=link[name="Charlie"]')
        ->assertDontSeeIn('#main tbody', 'Bravo');
});

it('filters the entries by a condition', function () {
    visitCp('content/entries/articles')
        ->click('#main >> role=button[name="Filter results"]')
        ->click('role=button[name="Add a filter"]')
        ->click('role=button[name="Title"]')
        ->type('role=textbox[name="Title"]', 'Bravo')
        ->click('role=button[name="Apply"]')
        ->assertQueryStringHas('condition')
        ->assertDontSeeIn('#main tbody', 'Alpha')
        ->assertVisible('#main tbody >> role=link[name="Bravo"]')
        ->assertDontSeeIn('#main tbody', 'Charlie');
});
