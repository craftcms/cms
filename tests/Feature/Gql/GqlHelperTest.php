<?php

declare(strict_types=1);

use craft\models\GqlSchema;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlHelper;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;

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
        ->and(GqlHelper::canQueryAssets())->toBeFalse();
});

it('creates union types and full-access schemas', function () {
    $unionType = GqlHelper::getUnionType('SomeUnion', ['one', 'two'], fn () => 'one');
    $schema = GqlHelper::createFullAccessSchema();

    expect($unionType)->toBeInstanceOf(UnionType::class)
        ->and($schema->scope)->not->toBeEmpty();
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
