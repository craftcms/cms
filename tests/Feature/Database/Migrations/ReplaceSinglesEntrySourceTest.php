<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Conditions\SectionTypeConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
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

/** @param array<string, mixed> $settings */
function saveSinglesFieldConfig(string $uid, string $type, array $settings): void
{
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->muteEvents = true;
    $projectConfig->set(ProjectConfig::PATH_FIELDS.".$uid", [
        'type' => $type,
        'settings' => ProjectConfigHelper::packAssociativeArrays($settings),
    ]);
    $projectConfig->muteEvents = false;
}

/** @return array<string, mixed> */
function singlesFieldSettings(string $uid): array
{
    return ProjectConfigHelper::unpackAssociativeArrays(app(ProjectConfig::class)->get(ProjectConfig::PATH_FIELDS.".$uid.settings"));
}

/** @return list<array<string, mixed>> */
function runReplaceSinglesEntrySourceMigration(): array
{
    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_11_14_000000_replace_singles_entry_source.php';
    $migration->up();
    app()->forgetScopedInstances();

    return app(ProjectConfig::class)->get(ProjectConfig::PATH_ELEMENT_SOURCES.'.'.Entry::class) ?? [];
}

/**
 * The singles’ own sources, which the migration puts first, on a Singles page.
 *
 * @return list<array<string, mixed>>
 */
function singlesPageSources(Section ...$singles): array
{
    return array_map(fn (Section $single) => [
        'type' => ElementSources::TYPE_NATIVE,
        'key' => "section:$single->uid",
        'page' => 'Singles',
        'disabled' => true,
    ], $singles);
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
    $custom = $sources[5];

    expect(array_slice($sources, 0, 2))->toEqualCanonicalizing(singlesPageSources($this->home, $this->about))
        ->and(array_column(array_slice($sources, 2), 'key'))->toBe(['*', "section:{$this->news->uid}", $custom['key']])
        ->and(array_column($sources, 'key'))->not->toContain('singles')
        ->and($custom['type'])->toBe(ElementSources::TYPE_CUSTOM)
        ->and($custom['key'])->toStartWith('custom:')
        ->and($custom['label'])->toBe('Singles')
        ->and($custom['page'])->toBe('Site')
        ->and($custom['tableAttributes'])->toBe(['status', 'dateUpdated'])
        ->and($custom['defaultSort'])->toBe(['dateUpdated', 'desc'])
        ->and($custom['defaultViewMode'])->toBe('cards')
        ->and($custom['showBadgeCount'])->toBeTrue()
        ->and($custom['condition']['conditionRules']['rules'][0] ?? $custom['condition']['conditionRules'][0])
        ->toMatchArray(['class' => SectionTypeConditionRule::class, 'values' => [SectionType::Single->value]]);
});

it('removes a disabled Singles source without replacing it, and gives sources saved without pages the Entries page', function () {
    saveOldEntrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles', 'disabled' => true],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->news->uid}"],
    ]);

    $sources = runReplaceSinglesEntrySourceMigration();

    expect(array_column($sources, 'type'))->not->toContain(ElementSources::TYPE_CUSTOM)
        ->and(array_slice($sources, 0, 2))->toEqualCanonicalizing(singlesPageSources($this->home, $this->about))
        ->and(array_slice($sources, 2))->toEqual([
            ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
            ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->news->uid}", 'page' => 'Entries'],
        ])
        ->and(app(ElementSources::class)->getSources(Entry::class, page: 'Entries')->pluck('key')->all())
        ->toBe(['*', "section:{$this->news->uid}"]);
});

it('saves sources that were never customized, so the custom Singles source is kept', function () {
    actingAs(User::find()->one());
    EntryModel::factory()->forSection($this->home)->create();
    EntryModel::factory()->forSection($this->about)->create();
    EntryModel::factory()->forSection($this->news)->count(3)->create();
    saveOldEntrySources(null);

    $sources = runReplaceSinglesEntrySourceMigration();
    $custom = $sources[3];

    expect(array_slice($sources, 0, 2))->toEqualCanonicalizing(singlesPageSources($this->home, $this->about))
        ->and($sources[2])->toEqual(['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'])
        ->and($custom['label'])->toBe('Singles')
        ->and($custom['page'])->toBe('Entries')
        ->and(array_values(array_unique(array_column($sources, 'page'))))->toBe(['Singles', 'Entries'])
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

    expect(array_slice($sources, 0, 2))->toEqualCanonicalizing(singlesPageSources($this->home, $this->about))
        ->and($sources[5])->toEqual(['type' => ElementSources::TYPE_HEADING, 'heading' => '', 'page' => 'Entries'])
        ->and($sources[6]['type'])->toBe(ElementSources::TYPE_CUSTOM)
        ->and($sources[6]['page'])->toBe('Entries')
        ->and($sources)->toHaveCount(7);
});

it('leaves sources alone when there are no singles', function () {
    Section::query()->where('type', SectionType::Single)->delete();
    Sections::refreshSections();
    saveOldEntrySources(null);

    expect(runReplaceSinglesEntrySourceMigration())->toBe([]);
});

it('gives sections created afterward enabled sources on the first page shown', function () {
    saveOldEntrySources([['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles']]);
    runReplaceSinglesEntrySourceMigration();

    actingAs(User::find()->one());
    $contact = Section::factory()->create(['type' => SectionType::Single, 'handle' => 'contact']);
    $events = Section::factory()->create(['type' => SectionType::Channel, 'handle' => 'events']);
    Sections::refreshSections();
    app()->forgetScopedInstances();

    $sources = app(ElementSources::class)->getSources(Entry::class)->keyBy('key');

    expect($sources->keys()->all())->not->toContain("section:{$this->home->uid}")
        ->and($sources["section:$contact->uid"]['page'])->toBe('Entries')
        ->and($sources["section:$events->uid"]['page'])->toBe('Entries');
});

it('points fields restricted to the Singles source at the custom source', function () {
    saveOldEntrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles'],
    ]);
    saveSinglesFieldConfig('entries-field', Entries::class, ['sources' => ['singles', "section:{$this->news->uid}"]]);
    saveSinglesFieldConfig('link-field', Link::class, ['typeSettings' => ['entry' => ['sources' => ['singles']]]]);
    app(ProjectConfig::class)->muteEvents = true;

    $customKey = collect(runReplaceSinglesEntrySourceMigration())->firstWhere('type', ElementSources::TYPE_CUSTOM)['key'];

    expect(singlesFieldSettings('entries-field')['sources'])->toBe([$customKey, "section:{$this->news->uid}"])
        ->and(singlesFieldSettings('link-field')['typeSettings']['entry']['sources'])->toBe([$customKey]);
});

it('points fields restricted to a disabled Singles source at the singles’ own sources', function () {
    saveOldEntrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles', 'disabled' => true],
    ]);
    saveSinglesFieldConfig('entries-field', Entries::class, ['sources' => ['singles']]);
    app(ProjectConfig::class)->muteEvents = true;

    runReplaceSinglesEntrySourceMigration();

    expect(singlesFieldSettings('entries-field')['sources'])
        ->toEqualCanonicalizing(["section:{$this->home->uid}", "section:{$this->about->uid}"]);
});

it('moves CP navigation customizations of the Singles item to the custom source', function () {
    saveOldEntrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Site Content'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles', 'page' => 'Site Content'],
    ]);
    $nav = [
        'order' => ['nav-content-site-content' => ['nav-content-site-content-source-singles', 'nav-content-site-content-source-_2a']],
        'items' => ['nav-content-site-content-source-singles' => ['label' => 'Pages']],
    ];
    app(ProjectConfig::class)->set(ProjectConfig::PATH_CP_NAV.'.default', $nav);
    app(ProjectConfig::class)->set(ProjectConfig::PATH_CP_NAV.'.groups.group-uid.nav', $nav);

    $customKey = collect(runReplaceSinglesEntrySourceMigration())->firstWhere('type', ElementSources::TYPE_CUSTOM)['key'];
    $newId = 'nav-content-site-content-source-custom_3a'.substr($customKey, strlen('custom:'));

    foreach (['default', 'groups.group-uid.nav'] as $path) {
        $updated = app(ProjectConfig::class)->get(ProjectConfig::PATH_CP_NAV.".$path");

        expect($updated['items'])->toBe([$newId => ['label' => 'Pages']])
            ->and($updated['order']['nav-content-site-content'])->toBe([$newId, 'nav-content-site-content-source-_2a']);
    }
});

it('lists the Singles page before the other entry pages in a saved default navigation', function () {
    saveOldEntrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->news->uid}", 'page' => 'News'],
    ]);
    app(ProjectConfig::class)->set(ProjectConfig::PATH_CP_NAV.'.default', [
        'order' => [
            'root' => ['nav-dashboard', 'nav-group-content'],
            'nav-group-content' => ['nav-assets', 'nav-content-news', 'nav-content-entries'],
        ],
    ]);

    runReplaceSinglesEntrySourceMigration();

    expect(app(ProjectConfig::class)->get(ProjectConfig::PATH_CP_NAV.'.default.order'))->toEqual([
        'root' => ['nav-dashboard', 'nav-group-content'],
        'nav-group-content' => ['nav-assets', 'nav-content-singles', 'nav-content-news', 'nav-content-entries'],
    ]);

    $path = ProjectConfig::PATH_ELEMENT_SOURCES.'.'.Entry::class;
    app(ProjectConfig::class)->set($path, array_map(
        fn (array $source) => ($source['key'] ?? null) === "section:{$this->home->uid}" ? [...$source, 'disabled' => false] : $source,
        app(ProjectConfig::class)->get($path),
    ));
    actingAs(User::find()->one());
    app()->forgetScopedInstances();

    $content = collect(app(Navigation::class)->getTree())->firstWhere('id', 'nav-group-content');

    expect(array_values(array_filter(array_column($content->subnav, 'id'), fn (string $id) => str_starts_with($id, 'nav-content-'))))
        ->toBe(['nav-content-singles', 'nav-content-news', 'nav-content-entries']);
});

it('lists the Singles page with the entry pages where they were moved to', function () {
    saveOldEntrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles', 'page' => 'Entries'],
    ]);
    app(ProjectConfig::class)->set(ProjectConfig::PATH_CP_NAV.'.default', [
        'order' => ['root' => ['nav-dashboard', 'nav-content-entries', 'nav-group-content']],
        'items' => ['nav-content-entries' => ['parent' => 'root']],
    ]);

    runReplaceSinglesEntrySourceMigration();

    expect(app(ProjectConfig::class)->get(ProjectConfig::PATH_CP_NAV.'.default'))->toEqual([
        'order' => ['root' => ['nav-dashboard', 'nav-content-singles', 'nav-content-entries', 'nav-group-content']],
        'items' => [
            'nav-content-entries' => ['parent' => 'root'],
            'nav-content-singles' => ['parent' => 'root'],
        ],
    ]);
});

it('leaves a default navigation without a saved order alone', function () {
    saveOldEntrySources([['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles', 'page' => 'Entries']]);
    app(ProjectConfig::class)->set(ProjectConfig::PATH_CP_NAV.'.default', ['items' => ['nav-graphql' => ['hidden' => true]]]);

    runReplaceSinglesEntrySourceMigration();

    expect(app(ProjectConfig::class)->get(ProjectConfig::PATH_CP_NAV.'.default'))->toBe(['items' => ['nav-graphql' => ['hidden' => true]]]);
});
