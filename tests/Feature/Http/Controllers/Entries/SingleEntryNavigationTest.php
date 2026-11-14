<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::find()->one());
    $this->cpTrigger = Cms::config()->cpTrigger;

    $this->single = Section::factory()->create([
        'type' => SectionType::Single,
        'name' => 'Contact details',
        'handle' => 'contactDetails',
        'inSinglesSource' => false,
    ]);
    $this->entry = EntryModel::factory()->forSection($this->single)->createElement(['title' => 'Northwind']);
});

/**
 * Puts Entry's sources on pages, in order.
 *
 * @param  list<array<string, mixed>>  $sources
 */
function singleNavSources(array $sources): void
{
    app(ElementSources::class)->saveSources(Entry::class, $sources);
    app()->forgetScopedInstances();
}

it('opens a single’s entry from its source', function () {
    get(cp_url("content/entries/{$this->single->handle}"))
        ->assertRedirect(Entry::find()->id($this->entry->id)->one()->getCpEditUrl());
});

it('opens a page on its single when that’s the source it shows', function () {
    singleNavSources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->single->uid}", 'page' => 'Site'],
    ]);

    get(cp_url('content/site'))
        ->assertRedirect(Entry::find()->id($this->entry->id)->one()->getCpEditUrl());
});

it('keeps a single’s index when it’s named by ?source=', function () {
    get(cp_url('content/entries?source='.rawurlencode("section:{$this->single->uid}")))
        ->assertOk();
});
