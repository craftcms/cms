<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\TypeLoader;
use craft\models\GqlSchema;
use craft\test\mockclasses\gql\MockDirective;
use craft\test\mockclasses\gql\MockType;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\Events\DefineGqlValidationRules;
use CraftCms\Cms\Gql\Events\ExecutedGqlQuery;
use CraftCms\Cms\Gql\Events\ExecutingGqlQuery;
use CraftCms\Cms\Gql\Events\RegisterGqlDirectives;
use CraftCms\Cms\Gql\Events\RegisterGqlQueries;
use CraftCms\Cms\Gql\Events\RegisterGqlSchemaComponents;
use CraftCms\Cms\Gql\Events\RegisterGqlTypes;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\User\Elements\User;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    app(Gql::class)->flushCaches();
    app(Gql::class)->setActiveSchema(new GqlSchema);
    Cms::config()->enableGraphqlCaching = false;
});

it('throws when no active schema is set', function () {
    app(Gql::class)->setActiveSchema();

    app(Gql::class)->getActiveSchema();
})->throws(GqlException::class, 'No schema is active.');

it('dispatches query registration events', function () {
    Event::listen(RegisterGqlQueries::class, function (RegisterGqlQueries $event) {
        $event->queries['mockQuery'] = [
            'type' => Type::string(),
            'args' => [],
            'resolve' => static fn () => 'mocked',
        ];
    });

    $queries = app(Gql::class)->getSchemaDef()->getQueryType()->getFields();

    expect($queries)->toHaveKey('mockQuery');
});

it('dispatches directive and type registration events', function () {
    Event::listen(RegisterGqlDirectives::class, function (RegisterGqlDirectives $event) {
        $event->directives[] = MockDirective::class;
    });

    Event::listen(RegisterGqlTypes::class, function (RegisterGqlTypes $event) {
        $event->types[] = MockType::class;
    });

    MockType::getType();

    $schema = app(Gql::class)->getSchemaDef();

    expect($schema->getDirective(MockDirective::name()))->not->toBeNull()
        ->and($schema->getType(MockType::getName()))->not->toBeNull();
});

it('dispatches schema component registration events', function () {
    Event::listen(RegisterGqlSchemaComponents::class, function (RegisterGqlSchemaComponents $event) {
        $event->queries['Custom'] = [
            'custom.permission:read' => ['label' => 'Query custom data'],
        ];
    });

    $components = app(Gql::class)->getAllSchemaComponents();

    expect($components['queries'])->toHaveKey('Custom');
});

it('dispatches validation rule events', function () {
    Event::listen(DefineGqlValidationRules::class, function (DefineGqlValidationRules $event) {
        $event->validationRules = [];
    });

    expect(app(Gql::class)->getValidationRules())->toBe([]);
});

it('allows pre-execution listeners to short-circuit query execution', function () {
    $schema = app(Gql::class)->getPublicSchema();

    Event::listen(ExecutingGqlQuery::class, function (ExecutingGqlQuery $event) {
        $event->result = ['data' => 'override'];
    });

    expect(app(Gql::class)->executeQuery($schema, '{ping}'))->toBe(['data' => 'override']);
});

it('allows post-execution listeners to replace the result', function () {
    $schema = app(Gql::class)->getPublicSchema();

    Event::listen(ExecutedGqlQuery::class, function (ExecutedGqlQuery $event) {
        $event->result = ['data' => 'different override'];
    });

    expect(app(Gql::class)->executeQuery($schema, '{ping}'))->toBe(['data' => 'different override']);
});

it('flushes graphql registries and loaders', function () {
    UserInterface::getType();
    $typeName = User::GQL_TYPE_NAME;

    expect(GqlEntityRegistry::getEntity($typeName))->not->toBeFalse()
        ->and(TypeLoader::loadType($typeName))->toBeInstanceOf(ObjectType::class);

    app(Gql::class)->flushCaches();

    expect(GqlEntityRegistry::getEntity($typeName))->toBeFalse()
        ->and(fn () => TypeLoader::loadType($typeName))->toThrow(GqlException::class);
});

it('saves and deletes schemas through the new service', function () {
    $gql = app(Gql::class);
    $schema = new GqlSchema([
        'name' => 'Something',
        'scope' => ['usergroups.everyone:read'],
    ]);

    expect($gql->saveSchema($schema))->toBeTrue();

    $schemaId = $schema->id;

    expect($gql->getSchemaById($schemaId)?->uid)->toBe($schema->uid)
        ->and($gql->deleteSchemaById($schemaId))->toBeTrue()
        ->and($gql->getSchemaById($schemaId))->toBeNull();
});
