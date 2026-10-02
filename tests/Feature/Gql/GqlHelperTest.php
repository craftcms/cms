<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

beforeEach(function () {
    app(Gql::class)->flushCaches();
    Cms::config()->enableGraphqlCaching = false;
});

it('checks schema scope awareness and permissions', function () {
    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => [
            'sections.news:read',
            'volumes.images:edit',
        ],
    ]));

    expect(GqlHelper::isSchemaAwareOf('sections.news'))->toBeTrue()
        ->and(GqlHelper::canSchema('sections.news'))->toBeTrue()
        ->and(GqlHelper::canSchema('volumes.images', 'edit'))->toBeTrue()
        ->and(GqlHelper::canSchema('volumes.images'))->toBeFalse()
        ->and(GqlHelper::isSchemaAwareOf('users'))->toBeFalse();
});

it('extracts allowed entities and entity actions from the schema', function () {
    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => [
            'sections.news:read',
            'sections.news:edit',
            'sites.site-a:read',
        ],
    ]));

    expect(GqlHelper::extractAllowedEntitiesFromSchema())->toBe([
        'sections' => ['news'],
        'sites' => ['site-a'],
    ])->and(GqlHelper::extractEntityAllowedActions('sections.news'))->toBe(['read', 'edit']);
});

it('handles missing active schemas gracefully', function () {
    app(Gql::class)->setActiveSchema();

    expect(GqlHelper::isSchemaAwareOf('sections.news'))->toBeFalse()
        ->and(GqlHelper::canSchema('sections.news'))->toBeFalse()
        ->and(GqlHelper::extractAllowedEntitiesFromSchema())->toBe([]);
});

it('reports query permissions for common schema scopes', function () {
    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => [
            'sections.news:read',
            'usergroups.everyone:read',
        ],
    ]));

    expect(GqlHelper::canQueryEntries())->toBeTrue()
        ->and(GqlHelper::canQueryUsers())->toBeTrue()
        ->and(GqlHelper::canQueryAllUsers())->toBeTrue()
        ->and(GqlHelper::canQueryAssets())->toBeFalse();
});

it('creates and registers union types that resolve elements by their GraphQL type name', function () {
    $entryType = EntryType::factory()->create(['handle' => 'article']);
    $section = Section::factory()->withEntryTypes($entryType)->create();
    EntryTypes::refreshEntryTypes();
    $entry = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement();

    $articleType = new ObjectType(['name' => 'article_Entry', 'fields' => ['id' => Type::id()]]);
    $pageType = new ObjectType(['name' => 'page_Entry', 'fields' => ['id' => Type::id()]]);

    $unionType = GqlHelper::getUnionType('SomeUnion', [$articleType, $pageType]);

    expect($unionType->name)->toBe('SomeUnion')
        ->and($unionType->getTypes())->toBe([$articleType, $pageType])
        ->and($unionType->resolveType($entry, null, Mockery::mock(ResolveInfo::class)))->toBe('article_Entry')
        ->and(GqlHelper::getUnionType('SomeUnion', [$pageType]))->toBe($unionType);
});

it('wraps gql types in non-null wrappers', function () {
    $typeDef = [
        'name' => 'mock',
        'type' => Type::listOf(Type::string()),
        'args' => [],
    ];

    expect(GqlHelper::wrapInNonNull(Type::boolean()))->toEqual(Type::nonNull(Type::boolean()))
        ->and(GqlHelper::wrapInNonNull(Type::nonNull(Type::int())))->toEqual(Type::nonNull(Type::int()))
        ->and(GqlHelper::wrapInNonNull($typeDef))->toEqual([
            'name' => 'mock',
            'type' => Type::nonNull(Type::listOf(Type::string())),
            'args' => [],
        ]);
});
