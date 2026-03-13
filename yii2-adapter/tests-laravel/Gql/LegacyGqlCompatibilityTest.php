<?php

declare(strict_types=1);

use craft\events\ExecuteGqlQueryEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\models\GqlSchema;
use craft\services\Gql as LegacyGql;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Tests\TestCase;
use GraphQL\Type\Definition\Type;
use yii\base\Event;

uses(TestCase::class);

beforeEach(function() {
    Craft::$app->getGql()->flushCaches();
    Craft::$app->getGql()->setActiveSchema(new GqlSchema());
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
        $queries = Craft::$app->getGql()->getSchemaDef()->getQueryType()->getFields();

        expect($queries)->toHaveKey('legacyMockQuery');
    } finally {
        Event::off(LegacyGql::class, LegacyGql::EVENT_REGISTER_GQL_QUERIES, $handler);
    }
});

it('bridges legacy before-execute listeners', function() {
    $schema = Craft::$app->getGql()->getPublicSchema();
    $handler = function(ExecuteGqlQueryEvent $event) {
        $event->result = ['data' => 'legacy override'];
    };

    Event::on(LegacyGql::class, LegacyGql::EVENT_BEFORE_EXECUTE_GQL_QUERY, $handler);

    try {
        expect(Craft::$app->getGql()->executeQuery($schema, '{ping}'))->toBe(['data' => 'legacy override']);
    } finally {
        Event::off(LegacyGql::class, LegacyGql::EVENT_BEFORE_EXECUTE_GQL_QUERY, $handler);
    }
});
