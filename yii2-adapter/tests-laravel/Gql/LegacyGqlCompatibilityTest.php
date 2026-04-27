<?php

declare(strict_types=1);

use craft\events\ExecuteGqlQueryEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\gql\TypeLoader as LegacyTypeLoader;
use craft\helpers\Gql as LegacyGqlHelper;
use craft\models\GqlSchema;
use craft\models\GqlToken as LegacyGqlToken;
use craft\services\Gql as LegacyGql;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Tests\TestCase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use yii\base\Event;

uses(TestCase::class);

beforeEach(function() {
    app(\CraftCms\Cms\Gql\Gql::class)->flushCaches();
    app(\CraftCms\Cms\Gql\Gql::class)->setActiveSchema(new GqlSchema());
    Cms::config()->enableGraphqlCaching = false;
});

it('bridges legacy query registration listeners', function() {
    $handler = function(RegisterGqlQueriesEvent $event) {
        $event->queries['legacyMockQuery'] = [
            'type' => Type::string(),
            'args' => [],
            'resolve' => static fn() => 'legacy',
        ];
    };

    Event::on(LegacyGql::class, LegacyGql::EVENT_REGISTER_GQL_QUERIES, $handler);

    try {
        $queries = app(\CraftCms\Cms\Gql\Gql::class)->getSchemaDef()->getQueryType()->getFields();

        expect($queries)->toHaveKey('legacyMockQuery');
    } finally {
        Event::off(LegacyGql::class, LegacyGql::EVENT_REGISTER_GQL_QUERIES, $handler);
    }
});

it('bridges legacy before-execute listeners', function() {
    $schema = app(\CraftCms\Cms\Gql\Gql::class)->getPublicSchema();
    $handler = function(ExecuteGqlQueryEvent $event) {
        $event->result = ['data' => 'legacy override'];
    };

    Event::on(LegacyGql::class, LegacyGql::EVENT_BEFORE_EXECUTE_GQL_QUERY, $handler);

    try {
        expect(app(\CraftCms\Cms\Gql\Gql::class)->executeQuery($schema, '{ping}'))->toBe(['data' => 'legacy override']);
    } finally {
        Event::off(LegacyGql::class, LegacyGql::EVENT_BEFORE_EXECUTE_GQL_QUERY, $handler);
    }
});

it('keeps the legacy gql helper working against the new service', function() {
    app(\CraftCms\Cms\Gql\Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => ['sections.news:read'],
    ]));

    expect(LegacyGqlHelper::canSchema('sections.news'))->toBeTrue()
        ->and(LegacyGqlHelper::isSchemaAwareOf('sections.news'))->toBeTrue();
});

it('returns legacy token wrappers from the legacy gql service', function() {
    $modernToken = app(\CraftCms\Cms\Gql\Gql::class)->getPublicToken();
    $legacyToken = Craft::$app->getGql()->getPublicToken();

    expect($modernToken)->toBeInstanceOf(GqlToken::class)
        ->and($modernToken)->not->toBeInstanceOf(LegacyGqlToken::class)
        ->and($legacyToken)->toBeInstanceOf(LegacyGqlToken::class)
        ->and($legacyToken?->getSchema())->toBeInstanceOf(GqlSchema::class);
});

it('shares registry and loader state across modern and legacy namespaces', function() {
    GqlEntityRegistry::createEntity('SharedType', new ObjectType([
        'name' => 'SharedType',
        'fields' => [],
    ]));

    expect(LegacyTypeLoader::loadType('SharedType'))->toBeInstanceOf(ObjectType::class);
});
