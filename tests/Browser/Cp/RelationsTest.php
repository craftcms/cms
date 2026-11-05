<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::first());

    $related = Field::factory()->create(['name' => 'Related', 'handle' => 'related', 'type' => Entries::class]);
    Fields::refreshFields();

    $type = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([
            new EntryTitleField,
            new CustomField(config: ['fieldUid' => $related->uid]),
        ]))
        ->create(['name' => 'Article', 'handle' => 'article']);
    EntryTypes::refreshEntryTypes();

    $section = Section::factory()->withEntryTypes($type)->create(['name' => 'Articles', 'handle' => 'articles']);

    $this->entries = collect(['Alpha', 'Bravo', 'Charlie', 'Home'])->mapWithKeys(fn (string $title) => [
        $title => Entry::factory()->forSection($section)->forEntryType($type)->indexed()->createElement(['title' => $title]),
    ]);
});

/**
 * @return list<string>
 */
function relatedTitles(EntryElement $entry): array
{
    return EntryElement::find()->id($entry->id)->one()->getFieldValue('related')->collect()->pluck('title')->all();
}

it('relates entries chosen in the element selector', function () {
    $home = $this->entries['Home'];

    visitCp("content/entries/articles/$home->id")
        ->click('#main >> role=button[name="Add an entry"]')
        ->click('craft-element-selector-modal >> role=checkbox[name="Select Bravo"]')
        ->click('craft-element-selector-modal >> role=checkbox[name="Select Alpha"]')
        ->click('craft-element-selector-modal >> role=button[name="Add an entry"]')
        ->assertMissing('craft-element-selector-modal dialog[open]')
        ->assertVisible('role=group[name="Related"] >> role=checkbox[name="Select Alpha"]')
        ->click('#main >> role=button[name="Save"]')
        ->assertSee('Entry saved.');

    expect(relatedTitles($home))->toBe(['Alpha', 'Bravo']);
});

it('reorders and removes related entries', function () {
    $home = $this->entries['Home'];
    $home->setFieldValue('related', $this->entries->only(['Alpha', 'Bravo', 'Charlie'])->pluck('id')->all());
    Elements::saveElement($home);

    visitCp("content/entries/articles/$home->id")
        ->click('role=group[name="Related"] >> craft-chip:has-text("Alpha") >> role=button[name="Reorder"]')
        ->click('role=button[name="Move down"]')
        ->assertSeeIn('role=group[name="Related"] >> craft-chip >> nth=0', 'Bravo')
        ->click('role=group[name="Related"] >> craft-chip:has-text("Charlie") >> role=button[name="Actions"]')
        ->click('role=button[name="Remove"]')
        ->assertMissing('role=group[name="Related"] >> role=checkbox[name="Select Charlie"]')
        ->click('#main >> role=button[name="Save"]')
        ->assertSee('Entry saved.');

    expect(relatedTitles($home))->toBe(['Bravo', 'Alpha']);
});
