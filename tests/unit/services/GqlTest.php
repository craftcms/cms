<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\db\Table;
use craft\elements\GlobalSet;
use craft\elements\User;
use craft\errors\GqlException;
use craft\events\ExecuteGqlQueryEvent;
use craft\events\RegisterGqlDirectivesEvent;
use craft\events\RegisterGqlMutationsEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\fs\Local;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\TypeLoader;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\CategoryGroup;
use craft\models\EntryType;
use craft\models\GqlSchema;
use craft\models\GqlToken;
use craft\models\Section;
use craft\models\TagGroup;
use craft\models\UserGroup;
use craft\services\Categories;
use craft\services\Globals;
use craft\services\Gql;
use craft\services\Sections;
use craft\services\Tags;
use craft\services\UserGroups;
use craft\services\Volumes;
use craft\test\mockclasses\gql\MockDirective;
use craft\test\mockclasses\gql\MockType;
use craft\test\TestCase;
use GraphQL\Type\Definition\ObjectType;
use UnitTester;
use yii\base\Event;
use yii\base\Exception;

class GqlTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    protected function _before(): void
    {
        Craft::$app->getGql()->flushCaches();
        $gql = Craft::$app->getGql();
        $gql->setActiveSchema(new GqlSchema());

        // NO CACHING
        Craft::$app->getConfig()->getGeneral()->enableGraphqlCaching = false;
    }

    protected function _after(): void
    {
    }

    /**
     * Test getting active schema errors out if none set
     */
    public function testCreatingSchemaFail(): void
    {
        $this->expectExceptionMessage('No schema is active.');
        $this->expectException(GqlException::class);

        $gqlService = Craft::$app->getGql();
        $gqlService->setActiveSchema(null);
        $gqlService->getActiveSchema();
    }

    /**
     * Test schema creation.
     */
    public function testCreatingSchemaSuccess(): void
    {
        $schema = Craft::$app->getGql()->getSchemaDef();
        self::assertInstanceOf('GraphQL\Type\Schema', $schema);
    }

    /**
     * Test adding custom queries to schema
     */
    public function testRegisteringQuery(): void
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function(RegisterGqlQueriesEvent $event) {
            $event->queries['mockQuery'] = [
                'type' => [],
                'args' => [],
                'resolve' => [],
            ];
        });

        $queries = Craft::$app->getGql()->getSchemaDef()->getQueryType()->getFields();
        self::assertArrayHasKey('mockQuery', $queries);
    }

    /**
     * Test adding custom mutations to schema
     */
    public function testRegisteringMutation(): void
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_MUTATIONS, function(RegisterGqlMutationsEvent $event) {
            $event->mutations['mockMutation'] = [
                'type' => [],
                'args' => [],
                'resolve' => [],
            ];
        });

        $queries = Craft::$app->getGql()->getSchemaDef()->getMutationType()->getFields();
        self::assertArrayHasKey('mockMutation', $queries);
    }

    /**
     * Test schema validation by adding an invalid query.
     */
    public function testValidatingSchema(): void
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function(RegisterGqlQueriesEvent $event) {
            $event->queries['mockQuery'] = [
                'type' => 'no bueno',
            ];
        });

        $this->expectException('craft\errors\GqlException');
        Craft::$app->getGql()->getSchemaDef(null, true);
    }

    /**
     * Test if it's possible to execute a query
     */
    public function testExecuteQuery(): void
    {
        $gql = Craft::$app->getGql();
        $schema = $gql->getPublicSchema();
        $result = $gql->executeQuery($schema, '{ping}');
        self::assertEquals(['data' => ['ping' => 'pong']], $result);
    }

    /**
     * Test query events
     *
     * @throws Exception
     */
    public function testQueryEvents(): void
    {
        $gql = Craft::$app->getGql();
        $schema = $gql->getPublicSchema();

        Event::on(Gql::class, Gql::EVENT_BEFORE_EXECUTE_GQL_QUERY, function(ExecuteGqlQueryEvent $event) {
            $event->result = ['data' => 'override'];
        });

        $result = $gql->executeQuery($schema, '{ping}');
        self::assertEquals(['data' => 'override'], $result);

        Event::on(Gql::class, Gql::EVENT_AFTER_EXECUTE_GQL_QUERY, function(ExecuteGqlQueryEvent $event) {
            $event->result = ['data' => 'different override'];
        });

        $result = $gql->executeQuery($schema, '{ping}');
        self::assertEquals(['data' => 'different override'], $result);
    }

    /**
     * Test whether querying fills caches, if caching enabled.
     *
     * @throws \Exception
     */
    public function testQueryingFillsCache(): void
    {
        $cache = [];
        $cacheKey = 'testKey';

        $gql = $this->make(Craft::$app->getGql(), [
            'setCachedResult' => function($key, $value) use (&$cache, $cacheKey) {
                $cache[$cacheKey] = $value;
            },
        ]);

        Craft::$app->getConfig()->getGeneral()->enableGraphqlCaching = true;

        $schema = $gql->getPublicSchema();

        self::assertArrayNotHasKey($cacheKey, $cache);
        $result = $gql->executeQuery($schema, '{ping}');
        self::assertEquals($cache[$cacheKey], $result);
    }

    /**
     * Test adding custom directives to schema
     */
    public function testRegisteringDirective(): void
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_DIRECTIVES, function(RegisterGqlDirectivesEvent $event) {
            $event->directives[] = MockDirective::class;
        });

        $directive = Craft::$app->getGql()->getSchemaDef()->getDirective(MockDirective::name());
        self::assertInstanceOf('GraphQL\Type\Definition\Directive', $directive);
    }

    /**
     * Test adding custom types to schema
     */
    public function testRegisteringType(): void
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_TYPES, function(RegisterGqlTypesEvent $event) {
            $event->types[] = MockType::class;
        });

        // Simulate it being reference in the schema or being defined by a generator
        MockType::getType();

        $mockType = Craft::$app->getGql()->getSchemaDef()->getType(MockType::getName());
        self::assertInstanceOf('GraphQL\Type\Definition\ScalarType', $mockType);
    }

    /**
     * Test if flushing works.
     */
    public function testFlushingCaches(): void
    {
        // Generate types by creating the interface.
        UserInterface::getType();
        $typeName = User::gqlTypeNameByContext(null);

        self::assertNotFalse(GqlEntityRegistry::getEntity($typeName));
        self::assertInstanceOf(ObjectType::class, TypeLoader::loadType($typeName));

        Craft::$app->getGql()->flushCaches();

        self::assertFalse(GqlEntityRegistry::getEntity($typeName));
        $this->tester->expectThrowable(GqlException::class, function() use ($typeName) {
            TypeLoader::loadType($typeName);
        });
    }

    /**
     * Test if token affects the schema.
     *
     * @throws GqlException
     * @throws Exception
     */
    public function testTokenAffectSchema(): void
    {
        $gqlService = Craft::$app->getGql();

        $gqlSchema = new GqlSchema(['id' => random_int(1, 1000), 'name' => 'Something', 'scope' => ['usergroups.everyone:read']]);
        $schema = $gqlService->getSchemaDef($gqlSchema);

        $gqlService->flushCaches();

        $gqlSchema = new GqlSchema(['id' => random_int(1, 1000), 'name' => 'Something', 'scope' => ['volumes.someVolume:read']]);
        self::assertNotEquals($schema, $gqlService->getSchemaDef($gqlSchema));
    }

    /**
     * Test if permission list is being generated
     */
    public function testPermissionListGenerated(): void
    {
        $sectionService = $this->make(Sections::class, [
            'getAllSections' => [
                new Section([
                    'id' => 1,
                    'uid' => 'sectionUid',
                    'name' => 'Test section',
                    'type' => 'channel',
                ]),
                new Section([
                    'id' => 2,
                    'uid' => 'otherSectionUid',
                    'name' => 'Other test section',
                    'type' => 'single',
                ]),
            ],
            'getAllEntryTypes' => [
                new EntryType([
                    'id' => 1,
                    'uid' => 'entryTypeUid',
                    'name' => 'Test entry type',
                    'sectionId' => 1,
                ]),
                new EntryType([
                    'id' => 2,
                    'uid' => 'entryTypeUid',
                    'name' => 'Test entry type',
                    'sectionId' => 2,
                ]),
            ],
        ]);

        $volumeService = $this->make(Volumes::class, [
            'getAllVolumes' => [
                new Local([
                    'id' => 1,
                    'name' => 'Test volume',
                    'uid' => 'volumeUid',
                ]),
            ],
        ]);

        $globalService = $this->make(Globals::class, [
            'getAllSets' => [
                new GlobalSet([
                    'id' => 1,
                    'name' => 'Test global',
                    'uid' => 'globalUid',
                ]),
            ],
        ]);
        $categoryService = $this->make(Categories::class, [
            'getAllGroups' => [
                new CategoryGroup([
                    'id' => 1,
                    'name' => 'Test category group',
                    'uid' => 'categoryGroupUid',
                ]),
            ],
        ]);
        $tagService = $this->make(Tags::class, [
            'getAllTagGroups' => [
                new TagGroup([
                    'id' => 1,
                    'name' => 'Test tag group',
                    'uid' => 'tagGroupUid',
                ]),
            ],
        ]);

        $userGroupService = $this->make(UserGroups::class, [
            'getAllGroups' => [
                new UserGroup([
                    'id' => 1,
                    'name' => 'Test user group',
                    'uid' => 'userGroupUid',
                ]),
            ],
        ]);

        Craft::$app->set('sections', $sectionService);
        Craft::$app->set('volumes', $volumeService);
        Craft::$app->set('globals', $globalService);
        Craft::$app->set('categories', $categoryService);
        Craft::$app->set('tags', $tagService);
        Craft::$app->set('userGroups', $userGroupService);


        $allSchemaComponents = Craft::$app->getGql()->getAllSchemaComponents();
        self::assertNotEmpty($allSchemaComponents);
        self::assertArrayHasKey('queries', $allSchemaComponents);
        self::assertArrayHasKey('mutations', $allSchemaComponents);

        self::assertNotEmpty($allSchemaComponents['queries']['Entries'] ?? []);
        self::assertNotEmpty($allSchemaComponents['queries']['Assets'] ?? []);
        self::assertNotEmpty($allSchemaComponents['queries']['Global sets'] ?? []);
        self::assertNotEmpty($allSchemaComponents['queries']['Users'] ?? []);
        self::assertNotEmpty($allSchemaComponents['queries']['Categories'] ?? []);
        self::assertNotEmpty($allSchemaComponents['queries']['Tags'] ?? []);


        self::assertNotEmpty($allSchemaComponents['mutations']['Entries'] ?? []);
        self::assertNotEmpty($allSchemaComponents['mutations']['Assets'] ?? []);
        self::assertNotEmpty($allSchemaComponents['mutations']['Global sets'] ?? []);
        self::assertNotEmpty($allSchemaComponents['mutations']['Categories'] ?? []);
        self::assertNotEmpty($allSchemaComponents['mutations']['Tags'] ?? []);
    }

    /**
     * Test all sorts of ways to invalidate GraphQL cache.
     */
    public function testInvalidatingCache(): void
    {
        $gql = Craft::$app->getGql();
        $gql->invalidateCaches();

        $cacheKey = 'testKey';
        $cacheValue = ['testValue'];
        $gql->setCachedResult($cacheKey, $cacheValue);

        $schema = new GqlSchema([
            'name' => StringHelper::randomString(15),
            'scope' => [],
        ]);

        self::assertEquals($cacheValue, $gql->getCachedResult($cacheKey));

        // Make sure saving a schema invalidates caches
        $gql->saveSchema($schema);
        self::assertNull($gql->getCachedResult($cacheKey));
        $gql->deleteSchemaById($schema->id);
    }

    /**
     * Test all Gql Token operations.
     *
     * @throws Exception
     */
    public function testTokenOperations(): void
    {
        $gql = Craft::$app->getGql();

        Craft::$app->getDb()->createCommand()
            ->truncateTable(Table::GQLTOKENS)
            ->execute();

        $accessToken = StringHelper::randomString();
        $tokenName = StringHelper::randomString(15);

        $token = new GqlToken([
            'name' => $tokenName,
            'accessToken' => $accessToken,
            'enabled' => true,
        ]);

        $gql->saveToken($token);

        // Test fetching token
        self::assertEquals($gql->getTokenById($token->id)->uid, $token->uid);
        self::assertEquals($gql->getTokenByUid($token->uid)->id, $token->id);
        self::assertEquals($gql->getTokenByAccessToken($token->accessToken)->id, $token->id);
        self::assertEquals($gql->getTokenByName($token->name)->id, $token->id);

        // Test fetching all tokens
        $allSchemas = $gql->getTokens();
        self::assertNotEmpty($allSchemas);

        // Test fetching public schema creates public token
        $publicToken = $gql->getPublicToken();
        self::assertEquals($publicToken->accessToken, GqlToken::PUBLIC_TOKEN);

        // Test deleting
        $gql->deleteTokenById($token->id);
        self::assertNull($gql->getTokenById($token->id));
    }

    /**
     * Test all Gql Schema operations.
     *
     * @throws Exception
     */
    public function testSchemaOperations(): void
    {
        $gql = Craft::$app->getGql();
        $gql->invalidateCaches();

        $schemaUid = StringHelper::UUID();
        $schema = new GqlSchema([
            'name' => StringHelper::randomString(15),
            'scope' => [],
            'uid' => $schemaUid,
        ]);

        $gql->saveSchema($schema);
        $schemaId = Db::idByUid(Table::GQLSCHEMAS, $schemaUid);

        // Test fetching schema
        self::assertEquals($gql->getSchemaById($schemaId)->uid, $schemaUid);
        self::assertEquals($gql->getSchemaByUid($schemaUid)->id, $schemaId);

        // Test fetching all schemas
        $allSchemas = Craft::$app->getGql()->getSchemas();
        self::assertNotEmpty($allSchemas);

        // Test deleting
        $gql->deleteSchemaById($schemaId);
        self::assertNull($gql->getSchemaById($schemaId));
    }
}
