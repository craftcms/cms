<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Structure\Structures;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::first());

    $structure = Structure::factory()->create();
    $type = EntryType::factory()->create(['name' => 'Page', 'handle' => 'page']);
    $section = Section::factory()->withEntryTypes($type)->create([
        'name' => 'Pages',
        'handle' => 'pages',
        'type' => SectionType::Structure,
        'structureId' => $structure->id,
    ]);
    Sections::refreshSections();

    foreach (['Alpha', 'Bravo', 'Charlie'] as $title) {
        $entry = Entry::factory()->forSection($section)->forEntryType($type)->createElement(['title' => $title]);
        app(Structures::class)->appendToRoot($structure->id, $entry, Mode::Insert);
    }
});

it('rearranges entries from the reorder menu', function () {
    visitCp('content/entries/pages?viewMode=structure')
        ->click('#main tbody tr:has-text("Bravo") >> role=button[name="Reorder"]')
        ->click('role=button[name="Indent"]')
        ->assertSeeIn('#main tbody tr:has-text("Bravo")', 'Level 2')
        ->click('#main >> role=heading[level=1]')
        ->click('#main tbody tr:has-text("Charlie") >> role=button[name="Reorder"]')
        ->click('role=button[name="Move up"]')
        ->assertVisible('#main tbody tr:nth-child(1) >> role=link[name="Charlie"]');

    expect(EntryElement::find()->section('pages')->orderBy('lft')->collect()
        ->map(fn (EntryElement $entry) => "$entry->level $entry->title")->all())
        ->toBe(['1 Charlie', '1 Alpha', '2 Bravo']);
});

it('nests an entry by dragging it onto another', function () {
    visitCp('content/entries/pages?viewMode=structure')
        ->drag('#main tbody tr:has-text("Charlie") >> role=button[name="Reorder"]', '#main tbody tr:has-text("Alpha")')
        ->assertSeeIn('#main tbody tr:has-text("Charlie")', 'Level 2');

    expect(EntryElement::find()->section('pages')->orderBy('lft')->collect()
        ->map(fn (EntryElement $entry) => "$entry->level $entry->title")->all())
        ->toBe(['1 Alpha', '2 Charlie', '1 Bravo']);
});
