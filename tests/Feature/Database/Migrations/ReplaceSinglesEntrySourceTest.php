<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Conditions\SectionTypeConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->home = Section::factory()->create(['type' => SectionType::Single, 'name' => 'Home', 'handle' => 'home']);
    $this->about = Section::factory()->create(['type' => SectionType::Single, 'name' => 'About', 'handle' => 'about']);
    $this->news = Section::factory()->create(['type' => SectionType::Channel, 'name' => 'News', 'handle' => 'news']);
    Sections::refreshSections();
});

/** @param list<array<string, mixed>>|null $sources */
function saveOldEntrySources(?array $sources): void
{
    app(ProjectConfig::class)->set(ProjectConfig::PATH_ELEMENT_SOURCES.'.'.Entry::class, $sources);
}

/** @return list<array<string, mixed>> */
function runReplaceSinglesEntrySourceMigration(): array
{
    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_11_14_000000_replace_singles_entry_source.php';
    $migration->up();
    app()->forgetScopedInstances();

    return app(ProjectConfig::class)->get(ProjectConfig::PATH_ELEMENT_SOURCES.'.'.Entry::class) ?? [];
}

it('replaces an enabled Singles source with a custom one configured the same way', function () {
    saveOldEntrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->news->uid}", 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_HEADING, 'heading' => 'Site', 'page' => 'Site'],
        [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => 'singles',
            'page' => 'Site',
            'tableAttributes' => ['status', 'dateUpdated'],
            'defaultSort' => ['dateUpdated', 'desc'],
            'defaultViewMode' => 'cards',
            'showBadgeCount' => true,
        ],
    ]);

    $sources = runReplaceSinglesEntrySourceMigration();
    $custom = $sources[3];

    expect(array_column($sources, 'key'))->not->toContain('singles')
        ->and($custom['type'])->toBe(ElementSources::TYPE_CUSTOM)
        ->and($custom['key'])->toStartWith('custom:')
        ->and($custom['label'])->toBe('Singles')
        ->and($custom['page'])->toBe('Site')
        ->and($custom['tableAttributes'])->toBe(['status', 'dateUpdated'])
        ->and($custom['defaultSort'])->toBe(['dateUpdated', 'desc'])
        ->and($custom['defaultViewMode'])->toBe('cards')
        ->and($custom['showBadgeCount'])->toBeTrue()
        ->and($custom['condition']['conditionRules']['rules'][0] ?? $custom['condition']['conditionRules'][0])
        ->toMatchArray(['class' => SectionTypeConditionRule::class, 'values' => [SectionType::Single->value]])
        ->and(array_slice($sources, 4))->toEqualCanonicalizing([
            ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->home->uid}", 'disabled' => true, 'page' => 'Site'],
            ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->about->uid}", 'disabled' => true, 'page' => 'Site'],
        ]);
});

it('removes a disabled Singles source without replacing it', function () {
    saveOldEntrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles', 'disabled' => true],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->news->uid}"],
    ]);

    $sources = runReplaceSinglesEntrySourceMigration();

    expect(array_column($sources, 'type'))->not->toContain(ElementSources::TYPE_CUSTOM)
        ->and(array_column($sources, 'key'))->toBe([
            '*',
            "section:{$this->about->uid}",
            "section:{$this->home->uid}",
            "section:{$this->news->uid}",
        ])
        ->and($sources[1]['disabled'])->toBeTrue()
        ->and($sources[2]['disabled'])->toBeTrue();
});

it('saves sources that were never customized, so the custom Singles source is kept', function () {
    actingAs(User::find()->one());
    EntryModel::factory()->forSection($this->home)->create();
    EntryModel::factory()->forSection($this->about)->create();
    EntryModel::factory()->forSection($this->news)->count(3)->create();
    saveOldEntrySources(null);

    $sources = runReplaceSinglesEntrySourceMigration();
    $custom = $sources[1];

    expect($sources[0])->toEqual(['type' => ElementSources::TYPE_NATIVE, 'key' => '*'])
        ->and($custom['label'])->toBe('Singles')
        ->and(array_column($sources, 'key'))->toContain("section:{$this->news->uid}")
        ->and(collect($sources)->firstWhere('heading', 'Channels'))->not->toBeNull()
        ->and(collect($sources)->firstWhere('heading', 'Singles'))->toBeNull()
        ->and(app(ElementSources::class)->getSources(Entry::class)->pluck('key')->all())
        ->not->toContain("section:{$this->home->uid}", "section:{$this->about->uid}");

    get('/'.Cms::config()->cpTrigger.'/content/entries?'.http_build_query(['source' => $custom['key'], 'viewMode' => 'cards']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('pagination.total', 2));
});

it('adds the Singles source that saved sources left out, where it was shown', function () {
    saveOldEntrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_HEADING, 'heading' => 'Channels', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->news->uid}", 'page' => 'Entries'],
    ]);

    $sources = runReplaceSinglesEntrySourceMigration();

    expect($sources[3])->toEqual(['type' => ElementSources::TYPE_HEADING, 'heading' => '', 'page' => 'Entries'])
        ->and($sources[4]['type'])->toBe(ElementSources::TYPE_CUSTOM)
        ->and($sources[4]['page'])->toBe('Entries')
        ->and(array_column(array_slice($sources, 5), 'disabled'))->toBe([true, true]);
});

it('leaves sources alone when there are no singles', function () {
    Section::query()->where('type', SectionType::Single)->delete();
    Sections::refreshSections();
    saveOldEntrySources(null);

    expect(runReplaceSinglesEntrySourceMigration())->toBe([]);
});

it('gives singles created afterward enabled sources', function () {
    saveOldEntrySources([['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles']]);
    runReplaceSinglesEntrySourceMigration();

    actingAs(User::find()->one());
    $contact = Section::factory()->create(['type' => SectionType::Single, 'handle' => 'contact']);
    Sections::refreshSections();
    app()->forgetScopedInstances();

    expect(app(ElementSources::class)->getSources(Entry::class)->pluck('key')->all())
        ->toContain("section:$contact->uid")
        ->not->toContain("section:{$this->home->uid}");
});
