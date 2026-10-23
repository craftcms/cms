<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem;
use CraftCms\Cms\Cp\CommandPalette\Contracts\SearchProvider;
use CraftCms\Cms\Cp\CommandPalette\SearchProviders\GlossarySearchProvider;
use CraftCms\Cms\Cp\Events\CommandPaletteActionsResolving;
use CraftCms\Cms\Cp\Events\CommandPalettePagesResolving;
use CraftCms\Cms\Cp\Events\CommandPaletteSearchProvidersResolving;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Releases;
use CraftCms\Cms\Section\Models\Section as SectionModel;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    Edition::set(Edition::Pro);
    actingAs(User::findOne());
});

afterEach(function () {
    ClearCaches::flushState();
});

function paletteSearch(string $provider, string $query): array
{
    return getJson(route('craft.cp.command-palette.search', ['provider' => $provider, 'query' => $query]))
        ->assertOk()
        ->json('results');
}

function paletteDraft(User $actor): EntryElement
{
    $canonical = EntryModel::factory()->createElement(['title' => 'Palette original']);
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    $draft = app(Drafts::class)->createDraft(canonical: $canonical, creatorId: $actor->id, name: 'Palette draft');
    $draft->title = 'Palette change';

    if (! Elements::saveElement($draft)) {
        throw new RuntimeException('The command palette test draft could not be saved.');
    }

    return $draft;
}

it('requires authentication', function () {
    Auth::logout();

    getJson(route('craft.cp.command-palette.index'))->assertUnauthorized();
    getJson(route('craft.cp.command-palette.search', ['provider' => 'pages', 'query' => 'x']))->assertUnauthorized();
});

it('lists the available search providers and the actions that aren’t search-only', function () {
    $response = getJson(route('craft.cp.command-palette.index'))->assertOk();

    $actionIds = collect($response->json('actions'))->pluck('id');

    expect(collect($response->json('providers'))->pluck('handle')->all())
        ->toContain('pages', 'actions', 'elements-user')
        ->and($actionIds)->toContain('create-section', 'enable-maintenance-mode')
        ->and($actionIds->filter(fn (string $id) => str_starts_with($id, 'set-locale-')))->toBeEmpty()
        ->and($actionIds->filter(fn (string $id) => str_starts_with($id, 'set-language-')))->toBeEmpty()
        ->and($response->json('actions.0'))->not->toHaveKey('errors')
        ->and(collect($response->json('actions'))->firstWhere('id', 'create-section'))->not->toHaveKeys(['iconSvg', 'thumbHtml', 'action', 'view']);
});

it('finds search-only actions when searching', function () {
    $ids = collect(paletteSearch('actions', 'formatting locale'))->pluck('id');

    expect($ids)->not->toBeEmpty()
        ->and($ids->every(fn (string $id) => str_starts_with($id, 'set-locale-')))->toBeTrue();
});

it('leaves the actions the palette opened with to the palette to search', function () {
    expect(collect(getJson(route('craft.cp.command-palette.index'))->json('actions'))->pluck('id'))->toContain('enable-maintenance-mode')
        ->and(collect(paletteSearch('actions', 'maintenance mode'))->pluck('id'))->not->toContain('enable-maintenance-mode');
});

it('finds language actions by the language’s own name, which they show as a hint', function () {
    $action = collect(paletteSearch('actions', 'Deutsch'))->firstWhere('id', 'set-language-de');

    expect($action)->not->toBeNull()
        ->and($action['label'])->toBe('Set language to German')
        ->and($action['hint'])->toBe('Deutsch')
        ->and($action['hintLang'])->toBe('de');
});

it('doesn’t offer the language that’s already in effect', function () {
    $ids = collect(paletteSearch('actions', 'set language'))->pluck('id');

    expect($ids)->not->toBeEmpty()
        ->and($ids)->not->toContain('set-language-'.app()->getLocale());
});

it('rejects unknown providers and empty queries', function () {
    getJson(route('craft.cp.command-palette.search', ['provider' => 'nope', 'query' => 'x']))->assertNotFound();
    getJson(route('craft.cp.command-palette.search', ['provider' => 'pages']))->assertUnprocessable();
});

it('describes nav pages by where they sit in the nav', function () {
    $sections = collect(paletteSearch('pages', 'sections'))->firstWhere('label', 'Sections');

    expect($sections)->not->toBeNull()
        ->and($sections['href'])->toBe(cp_url('settings/sections'))
        ->and($sections['description'])->toBe("Settings →\u{00A0}Content");
});

it('gives nav pages without icons their parent’s', function () {
    $pages = collect(paletteSearch('pages', 'users'))->keyBy('label');
    $utilities = collect(paletteSearch('pages', 'utilities'))->keyBy('id');

    expect($pages['Users']['icon'])->not->toBeNull()
        ->and($pages['All users']['icon'])->toBe($pages['Users']['icon'])
        ->and($utilities['nav-utilities-updates']['icon'])->not->toBe($utilities['nav-utilities']['icon']);
});

it('lists settings edit pages for admins, findable by handle', function () {
    $section = SectionModel::factory()->create(['name' => 'Palette Section', 'handle' => 'paletteSection']);
    Sections::refreshSections();

    $page = collect(paletteSearch('pages', 'palette section'))->firstWhere('id', "section-$section->id");

    expect($page)->not->toBeNull()
        ->and($page['label'])->toBe('Palette Section settings')
        ->and($page['href'])->toBe(cp_url("settings/sections/$section->id"))
        ->and($page['hint'])->toBe('paletteSection')
        ->and(collect(paletteSearch('pages', 'paletteSection'))->pluck('id'))->toContain("section-$section->id");
});

it('shows an entry type’s own icon, in its color', function () {
    $entryType = EntryTypeModel::factory()->create(['name' => 'Palette Story', 'icon' => 'book-open', 'color' => 'teal']);
    EntryTypes::refreshEntryTypes();

    $page = collect(paletteSearch('pages', 'palette story'))->firstWhere('id', "entry-type-$entryType->id");

    expect($page['icon'])->toBe('book-open')
        ->and($page['iconColor'])->toBe('teal');
});

it('hints at a field’s handle and type, and shows its type’s icon', function () {
    $field = new PlainText(['name' => 'Palette Notes', 'handle' => 'paletteNotes']);
    app(Fields::class)->saveField($field);

    $page = collect(paletteSearch('pages', 'palette notes'))->firstWhere('id', "field-$field->id");

    expect($page['hint'])->toBe('paletteNotes · '.PlainText::displayName())
        ->and($page['icon'])->toBe('i-cursor');
});

it('keeps settings pages and actions from non-admins', function () {
    $section = SectionModel::factory()->create(['name' => 'Palette Guarded']);
    Sections::refreshSections();
    $user = UserModel::factory()->withPermissions(['accessCp'])->createElement(['admin' => false]);
    actingAs($user);

    $actionIds = collect(getJson(route('craft.cp.command-palette.index'))->assertOk()->json('actions'))->pluck('id');

    expect($actionIds)->not->toContain('create-section')
        ->and($actionIds)->not->toContain('enable-maintenance-mode')
        ->and(collect(paletteSearch('pages', 'sections'))->pluck('label'))->not->toContain('Sections')
        ->and(collect(paletteSearch('actions', 'delete palette guarded'))->pluck('id'))->not->toContain("delete-section-$section->id");
});

it('offers to create entries only in sections the user can create entries in', function () {
    $allowed = SectionModel::factory()->create(['name' => 'Palette Allowed']);
    $viewOnly = SectionModel::factory()->create(['name' => 'Palette View Only']);
    Sections::refreshSections();
    actingAs(UserModel::factory()->withPermissions([
        'accessCp',
        "viewEntries:$allowed->uid",
        "createEntries:$allowed->uid",
        "viewEntries:$viewOnly->uid",
    ])->createElement(['admin' => false]));

    $actionIds = collect(getJson(route('craft.cp.command-palette.index'))->assertOk()->json('actions'))->pluck('id');

    expect($actionIds)->toContain("create-entry-$allowed->handle")
        ->and($actionIds)->not->toContain("create-entry-$viewOnly->handle");
});

it('only finds entries in sections the user can view', function () {
    $entryType = EntryTypeModel::factory()->withFieldLayout()->create(['hasTitleField' => true]);
    $visible = SectionModel::factory()->withEntryTypes($entryType)->create();
    $hidden = SectionModel::factory()->withEntryTypes($entryType)->create();
    $entries = collect([$visible, $hidden])->map(fn (SectionModel $section) => EntryModel::factory()
        ->indexed()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Palette Permitted']));
    Sections::refreshSections();

    expect(collect(paletteSearch('elements-entry', 'Palette Permitted'))->pluck('id'))
        ->toContain("element-{$entries[0]->id}", "element-{$entries[1]->id}");

    actingAs(UserModel::factory()->withPermissions(['accessCp', "viewEntries:$visible->uid", "viewPeerEntries:$visible->uid"])->createElement(['admin' => false]));

    $ids = collect(paletteSearch('elements-entry', 'Palette Permitted'))->pluck('id');

    expect($ids)->toContain("element-{$entries[0]->id}")
        ->and($ids)->not->toContain("element-{$entries[1]->id}");
});

it('only offers user search to those who can view users', function (array $permissions, bool $offered) {
    actingAs(UserModel::factory()->withPermissions(['accessCp', ...$permissions])->createElement(['admin' => false]));

    $handles = collect(getJson(route('craft.cp.command-palette.index'))->assertOk()->json('providers'))->pluck('handle');

    expect($handles->contains('elements-user'))->toBe($offered);
})->with([
    'without viewUsers' => [[], false],
    'with viewUsers' => [['viewUsers'], true],
]);

it('only offers settings changes where admin changes are allowed', function () {
    Cms::config()->allowAdminChanges = false;

    $actionIds = collect(getJson(route('craft.cp.command-palette.index'))->assertOk()->json('actions'))->pluck('id');

    expect($actionIds)->not->toContain('create-section')
        ->and($actionIds)->toContain('enable-maintenance-mode');
});

it('offers to delete settings when searching, confirming first', function () {
    $section = SectionModel::factory()->create(['name' => 'Palette Doomed']);
    Sections::refreshSections();

    $action = collect(paletteSearch('actions', 'delete palette doomed'))->firstWhere('id', "delete-section-$section->id");
    $indexIds = collect(getJson(route('craft.cp.command-palette.index'))->assertOk()->json('actions'))->pluck('id');

    expect($action)->not->toBeNull()
        ->and($action['variant'])->toBe('danger')
        ->and($action['action'])->toMatchArray([
            'type' => 'http',
            'method' => 'DELETE',
            'url' => route('craft.cp.settings.sections.destroy', ['section' => $section->id]),
        ])
        ->and($action['action']['confirm'])->toContain('Palette Doomed')
        ->and($action['removedPage'])->toBe(route('craft.cp.settings.sections.edit', ['section' => $section->id]))
        ->and($action['removedPageRedirect'])->toBe(route('craft.cp.settings.sections.index'))
        ->and($action['hint'])->toBe($section->handle)
        ->and($indexIds)->not->toContain("delete-section-$section->id");
});

it('only offers to delete settings where admin changes are allowed', function () {
    Cms::config()->allowAdminChanges = false;
    $section = SectionModel::factory()->create(['name' => 'Palette Doomed']);
    Sections::refreshSections();

    expect(collect(paletteSearch('actions', 'delete palette doomed'))->pluck('id'))
        ->not->toContain("delete-section-$section->id");
});

it('offers the utilities’ tasks to admins', function () {
    ClearCaches::add('data', ['label' => 'Data caches', 'info' => 'Anything cached with `Cache::put`', 'action' => fn () => null]);
    ClearCaches::add('ide-helper', ['label' => 'IDE helper', 'action' => fn () => null]);

    $actions = collect(getJson(route('craft.cp.command-palette.index'))->assertOk()->json('actions'))->keyBy('id');

    expect($actions->keys())->toContain(
        'clear-all-caches',
        'run-queue',
        'retry-failed-jobs',
        'release-all-jobs',
        'rebuild-project-config',
        'check-for-updates',
        'update-asset-indexes',
        'sign-out',
        'toggle-field-handles',
    )
        ->and($actions['clear-all-caches']['action'])->toMatchArray([
            'type' => 'http',
            'method' => 'POST',
            'url' => cp_url('utilities/clear-caches'),
            'body' => ['caches' => '*'],
        ])
        ->and($actions['release-all-jobs']['action']['confirm'])->not->toBeEmpty()
        ->and($actions['rebuild-project-config']['action']['confirm'])->not->toBeEmpty()
        ->and(collect(paletteSearch('actions', 'clear'))->firstWhere('id', 'clear-cache-data')['hint'])->toBe('Anything cached with Cache::put')
        ->and(collect(paletteSearch('actions', 'clear'))->pluck('label', 'id')->all())->toMatchArray([
            'clear-cache-data' => 'Clear data caches',
            'clear-cache-ide-helper' => 'Clear IDE helper',
        ]);
});

it('offers each utility’s tasks only to those allowed to use it', function () {
    actingAs(UserModel::factory()->withPermissions(['accessCp', 'utility:clear-caches'])->createElement(['admin' => false]));

    $actionIds = collect(getJson(route('craft.cp.command-palette.index'))->assertOk()->json('actions'))->pluck('id');

    expect($actionIds)->toContain('clear-all-caches', 'sign-out')
        ->not->toContain('run-queue', 'retry-failed-jobs', 'rebuild-project-config', 'check-for-updates', 'update-asset-indexes', 'toggle-field-handles');
});

it('doesn’t offer to rebuild a read-only project config', function () {
    app(ProjectConfig::class)->readOnly = true;

    expect(collect(getJson(route('craft.cp.command-palette.index'))->assertOk()->json('actions'))->pluck('id'))
        ->not->toContain('rebuild-project-config');
});

it('finds entries the user can view, linking to their edit pages', function () {
    $entryType = EntryTypeModel::factory()->withFieldLayout()->create(['hasTitleField' => true, 'icon' => 'book-open', 'color' => 'teal']);
    $section = SectionModel::factory()->withEntryTypes($entryType)->create();
    $entry = EntryModel::factory()
        ->indexed()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Palette Searchable']);

    $result = collect(paletteSearch('elements-entry', 'Palette Searchable'))->firstWhere('id', "element-$entry->id");

    expect($result)->not->toBeNull()
        ->and($result['label'])->toBe('Palette Searchable')
        ->and($result['href'])->toBe($entry->getCpEditUrl())
        ->and($result['hint'])->toBe($section->name)
        ->and($result['icon'])->toBe('book-open')
        ->and($result['iconColor'])->toBe('teal')
        ->and($result['elementActions'])->not->toBeEmpty()
        ->and(collect($result['elementActions'])->every(fn (array $action) => ($action['type'] ?? null) === 'hr' || isset($action['behavior'])))->toBeTrue();
});

it('offers to delete releases that can still be changed', function () {
    $actor = User::findOne();
    $release = app(Releases::class)->create('Palette scrap', null, $actor);

    $action = collect(getJson(route('craft.cp.command-palette.index'))->assertOk()->json('actions'))
        ->firstWhere('id', "delete-release-$release->id");

    expect($action)->not->toBeNull()
        ->and($action['variant'])->toBe('danger')
        ->and($action['action'])->toMatchArray([
            'type' => 'http',
            'method' => 'DELETE',
            'url' => route('craft.cp.releases.destroy', ['release' => $release->id]),
        ])
        ->and($action['action']['confirm'])->not->toBeEmpty()
        ->and($action['hint'])->toBe('Draft');
});

it('keeps release actions to the permissions that allow them', function (array $permissions, array $offered, array $withheld) {
    $actor = User::findOne();
    $releases = app(Releases::class);
    $release = $releases->create('Palette gated', null, $actor);
    $releases->attach($release, paletteDraft($actor), $actor);
    actingAs(UserModel::factory()->withPermissions(['accessCp', ...$permissions])->createElement(['admin' => false]));

    $actionIds = collect(getJson(route('craft.cp.command-palette.index'))->assertOk()->json('actions'))->pluck('id');
    $ids = fn (array $templates) => array_map(fn (string $template) => sprintf($template, $release->id), $templates);

    foreach ($ids($offered) as $id) {
        expect($actionIds)->toContain($id);
    }

    foreach ($ids($withheld) as $id) {
        expect($actionIds)->not->toContain($id);
    }
})->with([
    'others' => [[], [], ['create-release', 'delete-release-%d', 'publish-release-%d', 'schedule-release-%d']],
    // Publishing is nested under managing, so a publisher always manages too.
    'managers' => [['manageReleases'], ['create-release', 'delete-release-%d'], ['publish-release-%d', 'schedule-release-%d']],
    'publishers' => [['manageReleases', 'publishReleases'], ['create-release', 'delete-release-%d', 'publish-release-%d', 'schedule-release-%d'], []],
]);

it('doesn’t offer to delete releases that can no longer be changed', function () {
    $release = app(Releases::class)->create('Palette locked', null, User::findOne());
    $release->update(['status' => ReleaseStatus::Scheduled]);

    expect(collect(getJson(route('craft.cp.command-palette.index'))->assertOk()->json('actions'))->pluck('id'))
        ->not->toContain("delete-release-$release->id");
});

it('offers to publish and schedule releases that can be published', function () {
    $actor = User::findOne();
    $releases = app(Releases::class);
    $release = $releases->create('Palette launch', null, $actor);
    $releases->attach($release, paletteDraft($actor), $actor);

    $actions = collect(getJson(route('craft.cp.command-palette.index'))->assertOk()->json('actions'))->keyBy('id');

    expect($actions["publish-release-$release->id"]['action'])->toMatchArray([
        'type' => 'http',
        'url' => route('craft.cp.releases.publish', ['release' => $release->id]),
    ])
        ->and($actions["publish-release-$release->id"]['action']['confirm'])->not->toBeEmpty()
        ->and($actions["schedule-release-$release->id"]['view'])->toBe([
            'type' => 'ui',
            'modalUrl' => route('craft.cp.releases.scheduleModal', ['release' => $release->id]),
            'actionUrl' => route('craft.cp.releases.schedule', ['release' => $release->id]),
        ]);
});

it('finds glossary terms, fetching the glossary once and keeping its links to the web', function () {
    Http::fake([
        GlossarySearchProvider::ENDPOINT => Http::response([
            [
                'title' => 'Entry',
                'slug' => 'entry',
                'url' => 'https://craftcms.com/glossary/entry',
                'summaryPlain' => 'A piece of content.',
                'docsLinks' => [
                    ['title' => 'Entries', 'url' => 'https://craftcms.com/docs/entries'],
                    ['title' => 'Sneaky', 'url' => 'javascript:alert(1)'],
                ],
            ],
            ['title' => 'Asset', 'slug' => 'asset', 'url' => 'https://craftcms.com/glossary/asset'],
        ]),
    ]);

    $results = paletteSearch('glossary', 'entry');
    paletteSearch('glossary', 'asset');

    expect($results)->toHaveCount(1)
        ->and($results[0]['label'])->toBe('Entry')
        ->and($results[0]['view'])->toBe([
            'type' => 'glossary',
            'definition' => 'A piece of content.',
            'url' => 'https://craftcms.com/glossary/entry',
            'docsLinks' => [['title' => 'Entries', 'url' => 'https://craftcms.com/docs/entries']],
        ]);

    Http::assertSentCount(1);
});

it('finds no glossary terms when craftcms.com can’t be reached', function () {
    Http::fake([GlossarySearchProvider::ENDPOINT => Http::response(null, 503)]);

    expect(paletteSearch('glossary', 'entry'))->toBe([]);
});

it('lets plugins register pages, actions and search providers', function () {
    Event::listen(fn (CommandPalettePagesResolving $event) => $event->pages[] = new CommandPaletteItem()
        ->id('plugin-page')
        ->label('Plugin Reports')
        ->href(cp_url('plugin/reports')));

    Event::listen(fn (CommandPaletteActionsResolving $event) => $event->actions[] = new CommandPaletteItem()
        ->id('plugin-action')
        ->label('Clear plugin caches')
        ->action(['type' => 'http', 'url' => cp_url('plugin/clear')]));

    Event::listen(fn (CommandPaletteSearchProvidersResolving $event) => $event->providers[] = new class implements SearchProvider
    {
        public function getHandle(): string
        {
            return 'plugin-things';
        }

        public function getLabel(): string
        {
            return 'Things';
        }

        public function isAvailable(CraftUser $user): bool
        {
            return true;
        }

        public function search(string $query, CraftUser $user): iterable
        {
            yield new CommandPaletteItem()->id('thing')->label("Thing matching $query");
        }
    });

    $index = getJson(route('craft.cp.command-palette.index'))->assertOk();

    expect(collect($index->json('providers'))->pluck('handle'))->toContain('plugin-things')
        ->and(collect($index->json('actions'))->pluck('id'))->toContain('plugin-action')
        ->and(collect(paletteSearch('pages', 'plugin reports'))->pluck('id'))->toContain('plugin-page')
        ->and(paletteSearch('plugin-things', 'widgets')[0]['label'])->toBe('Thing matching widgets');
});
