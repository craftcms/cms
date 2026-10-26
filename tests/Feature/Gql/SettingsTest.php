<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\Interfaces\Elements\Asset as AssetInterface;
use CraftCms\Cms\Gql\Interfaces\Elements\Entry as EntryInterface;
use CraftCms\Cms\Gql\Interfaces\Elements\User as UserInterface;
use CraftCms\Cms\Gql\Queries\Settings;
use CraftCms\Cms\Gql\Types\Elements\User as UserGqlType;
use CraftCms\Cms\Gql\Types\Section as SectionGqlType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Site\Data\Site as SiteData;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use GraphQL\Type\Definition\ResolveInfo;

beforeEach(function () {
    app(Gql::class)->flushCaches();
    Cms::config()->enableGraphqlCaching = false;
    Edition::set(Edition::Pro);
});

it('registers query-only schema components for settings', function () {
    $entryType = EntryType::factory()->create();
    Section::factory()->withEntryTypes($entryType)->create();
    Asset::factory()->createElement();
    CraftCms\Cms\User\Models\UserGroup::factory()->create();
    EntryTypes::refreshEntryTypes();

    $components = app(Gql::class)->getAllSchemaComponents();
    $queries = array_merge(...array_values($components['queries']));
    $mutations = array_merge(...array_values($components['mutations']));

    expect($queries)->toHaveKeys([
        'settings.sites:read',
        'settings.sections:read',
        'settings.entrytypes:read',
        'settings.volumes:read',
        'settings.usergroups:read',
    ])
        ->and(collect(array_keys($mutations))->filter(fn (string $key) => str_starts_with($key, 'settings.')))->toBeEmpty();
});

it('only adds settings fields when their component is in the schema', function (Closure $getFields, string $fieldName, string $component) {
    $gql = app(Gql::class);

    $gql->setActiveSchema(new GqlSchema(['scope' => ['usergroups.everyone:read']]));
    expect($getFields())->not->toHaveKey($fieldName);

    $gql->flushCaches();
    $gql->setActiveSchema(new GqlSchema(['scope' => ['usergroups.everyone:read', "$component:read"]]));
    expect($getFields())->toHaveKey($fieldName);
})->with([
    'element site' => [EntryInterface::getFieldDefinitions(...), 'site', 'settings.sites'],
    'entry section' => [EntryInterface::getFieldDefinitions(...), 'section', 'settings.sections'],
    'entry type' => [EntryInterface::getFieldDefinitions(...), 'type', 'settings.entrytypes'],
    'asset volume' => [AssetInterface::getFieldDefinitions(...), 'volume', 'settings.volumes'],
    'user groups' => [UserInterface::getFieldDefinitions(...), 'groups', 'settings.usergroups'],
    'section entry types' => [SectionGqlType::getFieldDefinitions(...), 'entryTypes', 'settings.entrytypes'],
]);

it('only adds top-level settings queries when their component is in the schema', function () {
    $gql = app(Gql::class);

    $gql->setActiveSchema(new GqlSchema(['scope' => ['usergroups.everyone:read']]));
    expect(Settings::getQueries())->toBe([]);

    foreach ([
        'sites' => ['sites', 'site'],
        'sections' => ['sections', 'section'],
        'entrytypes' => ['entryTypes', 'entryType'],
        'volumes' => ['volumes', 'volume'],
        'usergroups' => ['userGroups', 'userGroup'],
    ] as $component => $names) {
        $gql->setActiveSchema(new GqlSchema(['scope' => ["settings.$component:read"]]));
        expect(array_keys(Settings::getQueries()))->toBe($names);
    }
});

it('only returns disabled sites when the schema can query their elements', function () {
    $enabledSite = new SiteData(['uid' => 'enabled-site-uid', 'handle' => 'enabledSite', 'enabled' => true]);
    $disabledSite = new SiteData(['uid' => 'disabled-site-uid', 'handle' => 'disabledSite', 'enabled' => false]);
    Sites::partialMock()
        ->shouldReceive('getAllSites')
        ->andReturn(collect([$enabledSite, $disabledSite]));
    $gql = app(Gql::class);

    $gql->setActiveSchema(new GqlSchema(['scope' => ['settings.sites:read']]));
    expect(Settings::getQueries()['sites']['resolve'](null, []))->toBe([$enabledSite]);

    $gql->setActiveSchema(new GqlSchema(['scope' => ['settings.sites:read', 'sites.disabled-site-uid:read']]));
    expect(Settings::getQueries()['sites']['resolve'](null, []))->toBe([$enabledSite, $disabledSite]);
});

it('limits a user’s groups to the groups the schema can query users in', function () {
    $group1 = new UserGroup(['uid' => 'group-1-uid', 'handle' => 'group1']);
    $group2 = new UserGroup(['uid' => 'group-2-uid', 'handle' => 'group2']);
    $user = Mockery::mock(User::class)->makePartial();
    $user->shouldReceive('getGroups')->andReturn([$group1, $group2]);
    $resolveInfo = Mockery::mock(ResolveInfo::class);
    $resolveInfo->fieldName = 'groups';
    $gql = app(Gql::class);

    $gql->setActiveSchema(new GqlSchema(['scope' => ['settings.usergroups:read', 'usergroups.everyone:read']]));
    $type = new UserGqlType(['name' => 'User', 'fields' => []]);
    expect($type->resolveWithDirectives($user, [], null, $resolveInfo))->toBe([$group1, $group2]);

    $gql->setActiveSchema(new GqlSchema(['scope' => ['settings.usergroups:read', 'usergroups.group-1-uid:read']]));
    expect($type->resolveWithDirectives($user, [], null, $resolveInfo))->toBe([$group1]);
});

it('queries settings fields on elements', function () {
    $entryType = EntryType::factory()->create(['handle' => 'article', 'color' => Color::Red->value]);
    $section = Section::factory()->withEntryTypes($entryType)->create(['handle' => 'news', 'type' => 'channel']);
    EntryTypes::refreshEntryTypes();
    EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement();
    $asset = Asset::factory()->createElement();
    gqlActivateFullAccessSchema();

    graphQL(<<<'GQL'
        {
          entries(section: "news", limit: 1) {
            ... on EntryInterface {
              site { handle primary }
              section { handle type entryTypes { handle } }
              type { handle color }
            }
          }
        }
        GQL)
        ->assertOk()
        ->assertJsonMissingPath('errors')
        ->assertJsonPath('data.entries.0.site.handle', Sites::getPrimarySite()->handle)
        ->assertJsonPath('data.entries.0.site.primary', true)
        ->assertJsonPath('data.entries.0.section', ['handle' => 'news', 'type' => 'channel', 'entryTypes' => [['handle' => 'article']]])
        ->assertJsonPath('data.entries.0.type', ['handle' => 'article', 'color' => 'red']);

    graphQL("{ asset(id: {$asset->id}) { volume { handle } } }")
        ->assertOk()
        ->assertJsonPath('data.asset.volume.handle', $asset->volume->handle);
});

it('queries settings at the top level', function () {
    $entryType = EntryType::factory()->create(['handle' => 'article']);
    Section::factory()->withEntryTypes($entryType)->create(['handle' => 'news']);
    CraftCms\Cms\User\Models\UserGroup::factory()->create(['handle' => 'editors']);
    EntryTypes::refreshEntryTypes();
    gqlActivateFullAccessSchema();
    $primarySite = Sites::getPrimarySite();

    graphQL(<<<GQL
        {
          sites { handle enabled }
          site(handle: "{$primarySite->handle}") { handle }
          sections(handle: "news") { handle }
          section(handle: "missing") { handle }
          entryTypes(handle: "article") { handle }
          userGroups { handle }
          userGroup(handle: "editors") { handle }
        }
        GQL)
        ->assertOk()
        ->assertJsonMissingPath('errors')
        ->assertJsonPath('data.sites', [['handle' => $primarySite->handle, 'enabled' => true]])
        ->assertJsonPath('data.site', ['handle' => $primarySite->handle])
        ->assertJsonPath('data.sections', [['handle' => 'news']])
        ->assertJsonPath('data.section', null)
        ->assertJsonPath('data.entryTypes', [['handle' => 'article']])
        ->assertJsonPath('data.userGroups', [['handle' => 'editors']])
        ->assertJsonPath('data.userGroup', ['handle' => 'editors']);
});
