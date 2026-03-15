<?php

declare(strict_types=1);

use craft\elements\GlobalSet;
use craft\fs\Local;
use craft\models\CategoryGroup;
use craft\models\GqlSchema;
use craft\models\GqlToken;
use craft\models\TagGroup;
use craft\services\Categories;
use craft\services\Entries;
use craft\services\Globals;
use craft\services\Tags;
use craft\services\UserGroups;
use craft\test\mockclasses\gql\MockDirective;
use craft\test\mockclasses\gql\MockType;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Gql\Events\DefineGqlValidationRules;
use CraftCms\Cms\Gql\Events\ExecutedGqlQuery;
use CraftCms\Cms\Gql\Events\ExecutingGqlQuery;
use CraftCms\Cms\Gql\Events\RegisterGqlDirectives;
use CraftCms\Cms\Gql\Events\RegisterGqlMutations;
use CraftCms\Cms\Gql\Events\RegisterGqlQueries;
use CraftCms\Cms\Gql\Events\RegisterGqlSchemaComponents;
use CraftCms\Cms\Gql\Events\RegisterGqlTypes;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Interfaces\Elements\User as UserInterface;
use CraftCms\Cms\Gql\TypeLoader;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\DeprecatedConcepts;
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

it('dispatches mutation registration events', function () {
    Event::listen(RegisterGqlMutations::class, function (RegisterGqlMutations $event) {
        $event->mutations['mockMutation'] = [
            'type' => Type::string(),
            'args' => [],
            'resolve' => static fn () => 'mocked',
        ];
    });

    $mutations = app(Gql::class)->getSchemaDef()->getMutationType()->getFields();

    expect($mutations)->toHaveKey('mockMutation');
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

it('validates schemas when a registered field definition is invalid', function () {
    Event::listen(RegisterGqlQueries::class, function (RegisterGqlQueries $event) {
        $event->queries['mockQuery'] = [
            'type' => 'no bueno',
        ];
    });

    app(Gql::class)->getSchemaDef(null, true);
})->throws(GqlException::class);

it('executes a query through the new service', function () {
    $schema = app(Gql::class)->getPublicSchema();

    expect(app(Gql::class)->executeQuery($schema, '{ping}'))
        ->toBe(['data' => ['ping' => 'pong']]);
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

    $entriesService = Mockery::mock(Entries::class, [
        'getAllEntryTypes' => [$typeA, $typeB],
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

    $globalService = Mockery::mock(Globals::class, [
        'getAllSets' => [
            new GlobalSet([
                'id' => 1,
                'name' => 'Test global',
                'uid' => 'globalUid',
            ]),
        ],
    ]);
    $categoryService = Mockery::mock(Categories::class, [
        'getAllGroups' => [
            new CategoryGroup([
                'id' => 1,
                'name' => 'Test category group',
                'uid' => 'categoryGroupUid',
            ]),
        ],
    ]);
    $tagService = Mockery::mock(Tags::class, [
        'getAllTagGroups' => [
            new TagGroup([
                'id' => 1,
                'name' => 'Test tag group',
                'uid' => 'tagGroupUid',
            ]),
        ],
    ]);
    $userGroupService = Mockery::mock(UserGroups::class, [
        'getAllGroups' => [
            new UserGroup([
                'id' => 1,
                'name' => 'Test user group',
                'uid' => 'userGroupUid',
            ]),
        ],
    ]);

    Craft::$app->set('entries', $entriesService);
    Craft::$app->set('globals', $globalService);
    Craft::$app->set('categories', $categoryService);
    Craft::$app->set('tags', $tagService);
    Craft::$app->set('userGroups', $userGroupService);

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
            ->and($components['mutations']['Assets'] ?? [])->not->toBeEmpty()
            ->and(
                DeprecatedConcepts::supportsGlobalSets() ? ($components['queries']['Global Sets'] ?? []) : [true],
            )->not->toBeEmpty()
            ->and(
                DeprecatedConcepts::supportsCategories() ? ($components['queries']['Categories'] ?? []) : [true],
            )->not->toBeEmpty()
            ->and(
                DeprecatedConcepts::supportsTags() ? ($components['queries']['Tags'] ?? []) : [true],
            )->not->toBeEmpty()
            ->and(
                DeprecatedConcepts::supportsGlobalSets() ? ($components['mutations']['Global Sets'] ?? []) : [true],
            )->not->toBeEmpty()
            ->and(
                DeprecatedConcepts::supportsCategories() ? ($components['mutations']['Categories'] ?? []) : [true],
            )->not->toBeEmpty()
            ->and(
                DeprecatedConcepts::supportsTags() ? ($components['mutations']['Tags'] ?? []) : [true],
            )->not->toBeEmpty();
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
