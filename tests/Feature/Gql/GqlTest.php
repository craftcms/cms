<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\Events\ExecutedGqlQuery;
use CraftCms\Cms\Gql\Events\GqlQueryExecuting;
use CraftCms\Cms\Gql\Events\GqlSchemaComponentsResolving;
use CraftCms\Cms\Gql\Events\GqlValidationRulesResolving;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlDirectives;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\GqlMutations;
use CraftCms\Cms\Gql\GqlQueries;
use CraftCms\Cms\Gql\GqlTypes;
use CraftCms\Cms\Gql\Interfaces\Elements\User as UserInterface;
use CraftCms\Cms\Gql\Mutations\Mutation as BaseMutation;
use CraftCms\Cms\Gql\Queries\Query as BaseQuery;
use CraftCms\Cms\Gql\TypeLoader;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\TestClasses\Gql\MockDirective;
use CraftCms\Cms\Tests\TestClasses\Gql\MockType;
use CraftCms\Cms\User\Elements\User;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    app(Gql::class)->flushCaches();
    app(Gql::class)->setActiveSchema(new GqlSchema);
    Cms::config()->enableGraphqlCaching = false;
});

it('creates a schema definition', function () {
    expect(app(Gql::class)->getSchemaDef())->toBeInstanceOf(Schema::class);
});

it('throws when no active schema is set', function () {
    app(Gql::class)->setActiveSchema();

    app(Gql::class)->getActiveSchema();
})->throws(GqlException::class, 'No schema is active.');

it('uses registered query providers', function () {
    app(GqlQueries::class)->register(MockQuery::class);

    $queries = app(Gql::class)->getSchemaDef()->getQueryType()->getFields();

    expect($queries)->toHaveKey('mockQuery');
});

it('uses registered mutation providers', function () {
    app(GqlMutations::class)->register(MockMutation::class);

    $mutations = app(Gql::class)->getSchemaDef()->getMutationType()->getFields();

    expect($mutations)->toHaveKey('mockMutation');
});

it('uses currently registered directives', function () {
    $registry = app(GqlDirectives::class);
    $registry->register(MockDirective::class);

    $gql = app(Gql::class);
    $schema = $gql->getSchemaDef(new GqlSchema);

    expect($schema->getDirective(MockDirective::name()))->not()->toBeNull();

    $registry->remove(MockDirective::class);
    $gql->flushCaches();

    $schema = $gql->getSchemaDef(new GqlSchema([
        'scope' => ['directive:parseRefs', 'directive:transform'],
    ]));

    expect($schema->getDirective(MockDirective::name()))->toBeNull()
        ->and($schema->getDirective('parseRefs'))->not->toBeNull()
        ->and($schema->getDirective('transform'))->not->toBeNull();
});

it('coerces custom scalar directive arguments', function () {
    $registry = app(GqlDirectives::class);
    $registry->register(MockDirective::class);
    app(GqlQueries::class)->register(MockQuery::class);

    $gql = app(Gql::class);
    $result = $gql->executeQuery(new GqlSchema, '{ mockQuery @mockDirective(prefix: "custom") }');

    $registry->remove(MockDirective::class);

    expect($result)->toBe(['data' => ['mockQuery' => 'mockmocked']]);
});

it('uses registered types', function () {
    app(GqlTypes::class)->register(MockType::class);

    MockType::getType();

    $schema = app(Gql::class)->getSchemaDef();

    expect($schema->getType(MockType::getName()))->not->toBeNull();
});

it('dispatches schema component registration events', function () {
    Event::listen(GqlSchemaComponentsResolving::class, function (GqlSchemaComponentsResolving $event) {
        $event->queries['Custom'] = [
            'custom.permission:read' => ['label' => 'Query custom data'],
        ];
    });

    $components = app(Gql::class)->getAllSchemaComponents();

    expect($components['queries'])->toHaveKey('Custom');
});

it('dispatches validation rule events', function () {
    Event::listen(GqlValidationRulesResolving::class, function (GqlValidationRulesResolving $event) {
        $event->validationRules = [];
    });

    expect(app(Gql::class)->getValidationRules())->toBe([]);
});

it('validates schemas when a registered field definition is invalid', function () {
    app(GqlQueries::class)->register(InvalidMockQuery::class);

    app(Gql::class)->getSchemaDef(null, true);
})->throws(GqlException::class);

it('executes a query through the new service', function () {
    $schema = app(Gql::class)->getPublicSchema();

    expect(app(Gql::class)->executeQuery($schema, '{ping}'))
        ->toBe(['data' => ['ping' => 'pong']]);
});

it('allows pre-execution listeners to short-circuit query execution', function () {
    $schema = app(Gql::class)->getPublicSchema();

    Event::listen(GqlQueryExecuting::class, function (GqlQueryExecuting $event) {
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

it('fills the cache when querying through the new service', function () {
    Cms::config()->enableGraphqlCaching = true;

    $gql = app(Gql::class);
    $schema = $gql->getPublicSchema();

    $cacheKeyMethod = new ReflectionMethod(Gql::class, '_getCacheKey');
    $cacheKey = $cacheKeyMethod->invoke($gql, $schema, '{ping}', null, null, null);

    expect($cacheKey)->not->toBeNull()
        ->and($gql->getCachedResult($cacheKey))->toBeNull();

    $result = $gql->executeQuery($schema, '{ping}');

    expect($gql->getCachedResult($cacheKey))->toBe($result);
});

it('does not create cache keys for mutations', function (string $query, ?string $operationName) {
    Cms::config()->enableGraphqlCaching = true;

    $gql = app(Gql::class);
    $schema = $gql->getPublicSchema();

    $cacheKeyMethod = new ReflectionMethod(Gql::class, '_getCacheKey');

    expect($cacheKeyMethod->invoke($gql, $schema, $query, null, null, $operationName))->toBeNull();
})->with([
    'comment-prefixed mutation' => ["# comment\nmutation { bogus }", null],
    'fragment-prefixed mutation' => ['fragment PingFields on Query { ping } mutation { bogus }', null],
    'named mutation after a query' => ['query Read { ping } mutation Write { bogus }', 'Write'],
]);

it('flushes graphql registries and loaders', function () {
    UserInterface::getType();
    $typeName = User::GQL_TYPE_NAME;

    expect(GqlEntityRegistry::getEntity($typeName))->not->toBeFalse()
        ->and(TypeLoader::loadType($typeName))->toBeInstanceOf(ObjectType::class);

    app(Gql::class)->flushCaches();

    expect(GqlEntityRegistry::getEntity($typeName))->toBeFalse()
        ->and(fn () => TypeLoader::loadType($typeName))->toThrow(GqlException::class);
});

it('changes schema definitions when token scope changes', function () {
    $gql = app(Gql::class);

    $schemaA = $gql->getSchemaDef(new GqlSchema([
        'id' => random_int(1, 1000),
        'name' => 'Something',
        'scope' => ['usergroups.everyone:read'],
    ]));

    $gql->flushCaches();

    $schemaB = $gql->getSchemaDef(new GqlSchema([
        'id' => random_int(1, 1000),
        'name' => 'Something',
        'scope' => ['volumes.someVolume:read'],
    ]));

    expect($schemaB)->not->toBe($schemaA);
});

it('generates the expected permission list through the new service', function () {
    $typeA = new EntryType([
        'id' => 1,
        'uid' => 'entryTypeUid',
        'name' => 'Test entry type',
    ]);
    $typeB = new EntryType([
        'id' => 2,
        'uid' => 'otherEntryTypeUid',
        'name' => 'Other test entry type',
    ]);

    $sectionA = new Section([
        'id' => 1,
        'uid' => 'sectionUid',
        'name' => 'Test section',
        'type' => SectionType::Channel,
        'entryTypes' => [$typeA],
    ]);
    $sectionB = new Section([
        'id' => 2,
        'uid' => 'otherSectionUid',
        'name' => 'Other test section',
        'type' => SectionType::Single,
        'entryTypes' => [$typeB],
    ]);

    Sections::partialMock()
        ->shouldReceive('getAllSections')
        ->andReturn(collect([$sectionA, $sectionB]));

    Volumes::partialMock()
        ->shouldReceive('getAllVolumes')
        ->andReturn(collect([
            new Local([
                'id' => 1,
                'name' => 'Test volume',
                'uid' => 'volumeUid',
            ]),
        ]));

    $edition = Edition::get();
    Edition::set(Edition::Pro);

    try {
        $components = app(Gql::class)->getAllSchemaComponents();

        expect($components)
            ->toHaveKeys(['queries', 'mutations'])
            ->and($components['queries']['Entries'] ?? [])->not->toBeEmpty()
            ->and($components['queries']['Assets'] ?? [])->not->toBeEmpty()
            ->and($components['queries']['Users'] ?? [])->not->toBeEmpty()
            ->and($components['mutations']['Entries'] ?? [])->not->toBeEmpty()
            ->and($components['mutations']['Assets'] ?? [])->not->toBeEmpty();
    } finally {
        Edition::set($edition);
    }
});

it('invalidates cached results when schemas change', function () {
    $gql = app(Gql::class);
    $gql->invalidateCaches();

    $cacheKey = 'testKey';
    $cacheValue = ['testValue'];
    $gql->setCachedResult($cacheKey, $cacheValue);

    $schema = new GqlSchema([
        'name' => Str::random(15),
        'scope' => [],
    ]);

    expect($gql->getCachedResult($cacheKey))->toBe($cacheValue);

    $gql->saveSchema($schema);

    expect($gql->getCachedResult($cacheKey))->toBeNull();

    $gql->deleteSchemaById($schema->id);
});

it('supports token operations through the new service', function () {
    $gql = app(Gql::class);

    DB::table(Table::GQLTOKENS)->truncate();

    $token = new GqlToken([
        'name' => Str::random(15),
        'accessToken' => Str::random(),
        'enabled' => true,
    ]);

    expect($gql->saveToken($token))->toBeTrue()
        ->and($gql->getTokenById($token->id)?->uid)->toBe($token->uid)
        ->and($gql->getTokenByUid($token->uid)->id)->toBe($token->id)
        ->and($gql->getTokenByAccessToken($token->accessToken)->id)->toBe($token->id)
        ->and($gql->getTokenByName($token->name)?->id)->toBe($token->id)
        ->and($gql->getTokens())->not->toBeEmpty()
        ->and($gql->getPublicToken()?->accessToken)->toBe(GqlToken::PUBLIC_TOKEN);

    $gql->deleteTokenById($token->id);

    expect($gql->getTokenById($token->id))->toBeNull();
});

it('saves, queries, and deletes schemas through the new service', function () {
    $gql = app(Gql::class);
    $gql->invalidateCaches();

    $schemaUid = Str::uuid()->toString();
    $schema = new GqlSchema([
        'name' => Str::random(15),
        'scope' => [],
        'uid' => $schemaUid,
    ]);

    expect($gql->saveSchema($schema))->toBeTrue();

    $schemaId = DB::table(Table::GQLSCHEMAS)->idByUid($schemaUid);

    expect($gql->getSchemaById($schemaId)?->uid)->toBe($schemaUid)
        ->and($gql->getSchemaByUid($schemaUid)?->id)->toBe($schemaId)
        ->and($gql->getSchemas())->not->toBeEmpty()
        ->and($gql->deleteSchemaById($schemaId))->toBeTrue()
        ->and($gql->getSchemaById($schemaId))->toBeNull();
});

class MockQuery extends BaseQuery
{
    public static function getQueries(bool $checkToken = true): array
    {
        return [
            'mockQuery' => [
                'type' => Type::string(),
                'args' => [],
                'resolve' => static fn ($source, $arguments, $context, $resolveInfo) => GqlHelper::applyDirectives($source, $resolveInfo, 'mocked'),
            ],
        ];
    }
}

class InvalidMockQuery extends BaseQuery
{
    public static function getQueries(bool $checkToken = true): array
    {
        return [
            'mockQuery' => [
                'type' => 'no bueno',
            ],
        ];
    }
}

class MockMutation extends BaseMutation
{
    public static function getMutations(): array
    {
        return [
            'mockMutation' => [
                'type' => Type::string(),
                'args' => [],
                'resolve' => static fn () => 'mocked',
            ],
        ];
    }
}
