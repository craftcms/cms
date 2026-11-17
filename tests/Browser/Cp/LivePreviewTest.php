<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Uri;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::first());

    Aliases::set('@templates', dirname(__DIR__, 2).'/Support/templates');
});

it('previews unsaved changes', function () {
    $type = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([new EntryTitleField]))
        ->create(['name' => 'Article', 'handle' => 'article']);
    $section = Section::factory()->withEntryTypes($type)->create([
        'name' => 'Articles',
        'handle' => 'articles',
        'previewTargets' => [['label' => 'Primary entry page', 'urlFormat' => '{url}', 'refresh' => '1']],
    ]);
    $section->siteSettings()->update(['hasUrls' => true, 'uriFormat' => 'articles/{slug}', 'template' => 'entries/live-preview']);
    Sections::refreshSections();

    $entry = Entry::factory()->forSection($section)->forEntryType($type)->createElement(['title' => 'Before']);
    Sections::saveSection(Sections::getSectionById($section->id), runValidation: false);
    DB::table(Table::ELEMENTS_SITES)->where('elementId', $entry->id)->update(['slug' => 'before', 'uri' => 'articles/before']);

    $page = visitCp("content/entries/articles/$entry->id");

    $site = Sites::getPrimarySite();
    $site->setBaseUrl(Uri::of($page->url())->withPath('/')->withQuery([])->value());
    Sites::saveSite($site, runValidation: false);

    $page->refresh()
        ->click('#main >> role=button[name="Preview"]')
        ->assertSeeIn('iframe[title="Preview"] >> internal:control=enter-frame >> h1', 'Before')
        ->type('input[name="title"]', 'After')
        ->assertSeeIn('iframe[title="Preview"] >> internal:control=enter-frame >> h1', 'After');

    // The preview request leaves its draft as the placeholder element queries return, so read the stored title.
    expect(DB::table(Table::ELEMENTS_SITES)->where('elementId', $entry->id)->value('title'))->toBe('Before');
});
