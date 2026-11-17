<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

/**
 * Creates a “Home” page whose layout has a Matrix field of text blocks, shown in the given view mode.
 */
function createPageWithMatrix(string $viewMode): EntryElement
{
    $titleLayout = fn () => FieldLayout::factory()->withContentTab([new EntryTitleField]);

    $blockType = EntryType::factory()->withFieldLayout($titleLayout())
        ->create(['name' => 'Text block', 'handle' => 'textBlock']);

    $blocks = Field::factory()->create([
        'name' => 'Blocks',
        'handle' => 'blocks',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$blockType->id], 'viewMode' => $viewMode],
    ]);
    Fields::refreshFields();

    $pageType = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([
            new EntryTitleField,
            new CustomField(config: ['fieldUid' => $blocks->uid]),
        ]))
        ->create(['name' => 'Page', 'handle' => 'page']);
    EntryTypes::refreshEntryTypes();

    $section = Section::factory()->withEntryTypes($pageType)->create(['name' => 'Pages', 'handle' => 'pages']);

    return Entry::factory()->forSection($section)->forEntryType($pageType)->createElement(['title' => 'Home']);
}

/**
 * @return list<string>
 */
function nestedEntryTitles(EntryElement $owner): array
{
    return EntryElement::find()->ownerId($owner->id)->field('blocks')->status(null)->orderBy('sortOrder')->collect()->pluck('title')->all();
}

beforeEach(fn () => actingAs(User::first()));

it('adds nested entries inline in blocks view', function () {
    $page = createPageWithMatrix(Matrix::VIEW_MODE_BLOCKS);

    visitCp("content/entries/pages/$page->id")
        ->click('#main >> role=button[name="Add an entry"]')
        ->type('role=group[name="Blocks"] >> role=listitem >> nth=0 >> role=textbox[name=/^Title/]', 'Intro')
        ->click('#main >> role=button[name="Add an entry"]')
        ->type('role=group[name="Blocks"] >> role=listitem >> nth=1 >> role=textbox[name=/^Title/]', 'Outro')
        ->click('#main >> role=button[name="Save"]')
        ->assertSee('Entry saved.');

    expect(nestedEntryTitles($page))->toBe(['Intro', 'Outro']);
});

it('creates nested entries in a slideout in cards view', function () {
    $page = createPageWithMatrix(Matrix::VIEW_MODE_CARDS);

    visitCp("content/entries/pages/$page->id")
        ->click('#main >> role=button[name="New entry"]')
        ->type('role=dialog >> input[name="title"]', 'Intro')
        ->click('role=dialog >> role=button[name="Create entry"]')
        ->assertMissing('role=dialog')
        ->assertSeeIn('#main', 'Intro')
        ->click('#main >> role=button[name="Save"]')
        ->assertSee('Entry saved.');

    expect(nestedEntryTitles($page))->toBe(['Intro']);
});
