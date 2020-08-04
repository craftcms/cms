<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\services;

use Codeception\Test\Unit;
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
use craft\volumes\Local;
use GraphQL\Type\Definition\ObjectType;
use yii\base\Event;
use yii\base\InvalidArgumentException;

class GqlTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        Craft::$app->getGql()->flushCaches();
        $gql = Craft::$app->getGql();
        $gql->setActiveSchema(new GqlSchema());

        // NO CACHING
        Craft::$app->getConfig()->getGeneral()->enableGraphQlCaching = false;
    }

    protected function _after()
    {
    }

    /**
     * Test getting active schema errors out if none set
     *
     * @expectedException \craft\errors\GqlException
     * @expectedExceptionMessage No schema is active.
     */
    public function testCreatingSchemaFail()
    {
        $gqlService = Craft::$app->getGql();
        $gqlService->setActiveSchema(null);
        $gqlService->getActiveSchema();
    }

    /**
     * Test schema creation.
     */
    public function testCreatingSchemaSuccess()
    {
        $schema = Craft::$app->getGql()->getSchemaDef();
        $this->assertInstanceOf('GraphQL\Type\Schema', $schema);
    }

    /**
     * Test adding custom queries to schema
     */
    public function testRegisteringQuery()
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function(RegisterGqlQueriesEvent $event) {
            $event->queries['mockQuery'] = [
                'type' => [],
                'args' => [],
                'resolve' => []
            ];
        });

        $queries = Craft::$app->getGql()->getSchemaDef()->getQueryType()->getFields();
        $this->assertArrayHasKey('mockQuery', $queries);
    }

    /**
     * Test adding custom mutations to schema
     */
    public function testRegisteringMutation()
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_MUTATIONS, function(RegisterGqlMutationsEvent $event) {
            $event->mutations['mockMutation'] = [
                'type' => [],
                'args' => [],
                'resolve' => []
            ];
        });

        $queries = Craft::$app->getGql()->getSchemaDef()->getMutationType()->getFields();
        $this->assertArrayHasKey('mockMutation', $queries);
    }

    /**
     * Test schema validation by adding an invalid query.
     */
    public function testValidatingSchema()
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function(RegisterGqlQueriesEvent $event) {
            $event->queries['mockQuery'] = [
                'type' => 'no bueno'
            ];
        });

        $this->expectException('craft\errors\GqlException');
        Craft::$app->getGql()->getSchemaDef(null, true);
    }

    /**
     * Test if it's possible to execute a query
     */
    public function testExecuteQuery()
    {
        $gql = Craft::$app->getGql();
        $schema = $gql->getPublicSchema();
        $result = $gql->executeQuery($schema, '{ping}');
        $this->assertEquals(['data' => ['ping' => 'pong']], $result);
    }

    /**
     * Test query events
     *
     * @throws \yii\base\Exception
     */
    public function testQueryEvents()
    {
        $gql = Craft::$app->getGql();
        $schema = $gql->getPublicSchema();

        Event::on(Gql::class, Gql::EVENT_BEFORE_EXECUTE_GQL_QUERY, function(ExecuteGqlQueryEvent $event) {
            $event->result = ['data' => 'override'];
        });

        $result = $gql->executeQuery($schema, '{ping}');
        $this->assertEquals(['data' => 'override'], $result);

        Event::on(Gql::class, Gql::EVENT_AFTER_EXECUTE_GQL_QUERY, function(ExecuteGqlQueryEvent $event) {
            $event->result = ['data' => 'different override'];
        });

        $result = $gql->executeQuery($schema, '{ping}');
        $this->assertEquals(['data' => 'different override'], $result);
    }

    /**
     * Test whether querying fills caches, if caching enabled.
     *
     * @throws \Exception
     */
    public function testQueryingFillsCache()
    {
        $cache = [];
        $cacheKey = 'testKey';

        $gql = $this->make(Craft::$app->getGql(), [
            'setCachedResult' => function($key, $value) use (&$cache, $cacheKey) {
                $cache[$cacheKey] = $value;
            },
        ]);

        Craft::$app->getConfig()->getGeneral()->enableGraphQlCaching = true;

        $schema = $gql->getPublicSchema();

        $this->assertArrayNotHasKey($cacheKey, $cache);
        $result = $gql->executeQuery($schema, '{ping}');
        $this->assertEquals($cache[$cacheKey], $result);
    }

    /**
     * Test adding custom directives to schema
     */
    public function testRegisteringDirective()
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_DIRECTIVES, function(RegisterGqlDirectivesEvent $event) {
            $event->directives[] = MockDirective::class;
        });

        $directive = Craft::$app->getGql()->getSchemaDef()->getDirective(MockDirective::name());
        $this->assertInstanceOf('GraphQL\Type\Definition\Directive', $directive);
    }

    /**
     * Test adding custom types to schema
     */
    public function testRegisteringType()
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_TYPES, function(RegisterGqlTypesEvent $event) {
            $event->types[] = MockType::class;
        });

        // Simulate it being reference in the schema or being defined by a generator
        MockType::getType();

        $mockType = Craft::$app->getGql()->getSchemaDef()->getType(MockType::getName());
        $this->assertInstanceOf('GraphQL\Type\Definition\ScalarType', $mockType);
    }

    /**
     * Test if flushing works.
     */
    public function testFlushingCaches()
    {
        // Generate types by creating the interface.
        UserInterface::getType();
        $typeName = User::gqlTypeNameByContext(null);

        $this->assertNotFalse(GqlEntityRegistry::getEntity($typeName));
        $this->assertInstanceOf(ObjectType::class, TypeLoader::loadType($typeName));

        Craft::$app->getGql()->flushCaches();

        $this->assertFalse(GqlEntityRegistry::getEntity($typeName));
        $this->tester->expectThrowable(GqlException::class, function() use ($typeName) {
            TypeLoader::loadType($typeName);
        });
    }

    /**
     * Test if token affects the schema.
     *
     * @throws GqlException
     * @throws \yii\base\Exception
     */
    public function testTokenAffectSchema()
    {
        $gqlService = Craft::$app->getGql();

        $gqlSchema = new GqlSchema(['id' => random_int(1, 1000), 'name' => 'Something', 'scope' => ['usergroups.everyone:read']]);
        $schema = $gqlService->getSchemaDef($gqlSchema);

        $gqlService->flushCaches();

        $gqlSchema = new GqlSchema(['id' => random_int(1, 1000), 'name' => 'Something', 'scope' => ['volumes.someVolume:read']]);
        $this->assertNotEquals($schema, $gqlService->getSchemaDef($gqlSchema));
    }

    /**
     * Test if permission list is being generated
     */
    public function testPermissionListGenerated()
    {
        $sectionService = $this->make(Sections::class, [
            'getAllSections' => [
                new Section([
                    'id' => 1,
                    'uid' => 'sectionUid',
                    'name' => 'Test section',
                    'type' => 'channel'
                ]),
                new Section([
                    'id' => 2,
                    'uid' => 'otherSectionUid',
                    'name' => 'Other test section',
                    'type' => 'single'
                ]),
            ],
            'getAllEntryTypes' => [
                new EntryType([
                    'id' => 1,
                    'uid' => 'entryTypeUid',
                    'name' => 'Test entry type',
                    'sectionId' => 1
                ]),
                new EntryType([
                    'id' => 2,
                    'uid' => 'entryTypeUid',
                    'name' => 'Test entry type',
                    'sectionId' => 2
                ]),
            ]
        ]);

        $volumeService = $this->make(Volumes::class, [
            'getAllVolumes' => [
                new Local([
                    'id' => 1,
                    'name' => 'Test volume',
                    'uid' => 'volumeUid'
                ])
            ]
        ]);

        $globalService = $this->make(Globals::class, [
           'getAllSets' => [
               new GlobalSet([
                   'id' => 1,
                   'name' => 'Test global',
                   'uid' => 'globalUid'
               ])
           ]
        ]);
        $categoryService = $this->make(Categories::class, [
           'getAllGroups' => [
               new CategoryGroup([
                   'id' => 1,
                   'name' => 'Test category group',
                   'uid' => 'categoryGroupUid'
               ])
           ]
        ]);
        $tagService = $this->make(Tags::class, [
           'getAllTagGroups' => [
               new TagGroup([
                   'id' => 1,
                   'name' => 'Test tag group',
                   'uid' => 'tagGroupUid'
               ])
           ]
        ]);

        $userGroupService = $this->make(UserGroups::class, [
           'getAllGroups' => [
               new UserGroup([
                   'id' => 1,
                   'name' => 'Test user group',
                   'uid' => 'userGroupUid'
               ])
           ]
        ]);

        Craft::$app->set('sections', $sectionService);
        Craft::$app->set('volumes', $volumeService);
        Craft::$app->set('globals', $globalService);
        Craft::$app->set('categories', $categoryService);
        Craft::$app->set('tags', $tagService);
        Craft::$app->set('userGroups', $userGroupService);


        $allSchemaComponents = Craft::$app->getGql()->getAllSchemaComponents();
        $this->assertNotEmpty($allSchemaComponents);
        $this->assertArrayHasKey('queries', $allSchemaComponents);
        $this->assertArrayHasKey('mutations', $allSchemaComponents);

        $this->assertNotEmpty($allSchemaComponents['queries']['Entries'] ?? []);
        $this->assertNotEmpty($allSchemaComponents['queries']['Assets'] ?? []);
        $this->assertNotEmpty($allSchemaComponents['queries']['Global sets'] ?? []);
        $this->assertNotEmpty($allSchemaComponents['queries']['Users'] ?? []);
        $this->assertNotEmpty($allSchemaComponents['queries']['Categories'] ?? []);
        $this->assertNotEmpty($allSchemaComponents['queries']['Tags'] ?? []);


        $this->assertNotEmpty($allSchemaComponents['mutations']['Entries'] ?? []);
        $this->assertNotEmpty($allSchemaComponents['mutations']['Assets'] ?? []);
        $this->assertNotEmpty($allSchemaComponents['mutations']['Global sets'] ?? []);
        $this->assertNotEmpty($allSchemaComponents['mutations']['Categories'] ?? []);
        $this->assertNotEmpty($allSchemaComponents['mutations']['Tags'] ?? []);

    }

    /**
     * Test all sorts of ways to invalidate GraphQL cache.
     */
    public function testInvalidatingCache()
    {
        $gql = Craft::$app->getGql();
        $elements = Craft::$app->getElements();

        $gql->invalidateCaches();

        $cacheKey = 'testKey';
        $cacheValue = ['testValue'];
        $gql->setCachedResult($cacheKey, $cacheValue);

        $schema = new GqlSchema([
            'name' => StringHelper::randomString(15),
            'scope' => []
        ]);

        $this->assertEquals($gql->getCachedResult($cacheKey), $cacheValue);

        // Make sure saving a schema invalidates caches
        $gql->saveSchema($schema);
        $this->assertFalse($gql->getCachedResult($cacheKey));

        // Reset
        $gql->setCachedResult($cacheKey, $cacheValue);
        $this->assertEquals($gql->getCachedResult($cacheKey), $cacheValue);

        // Make sure saving an element invalidates caches.
        $user = new User();
        $user->username = 'testUser' . StringHelper::randomString(5);
        $user->email = 'user@a' . StringHelper::randomString(5) . '.com';
        $elements->saveElement($user);
        $this->assertFalse($gql->getCachedResult($cacheKey));

        // Reset
        $gql->setCachedResult($cacheKey, $cacheValue);
        $this->assertEquals($gql->getCachedResult($cacheKey), $cacheValue);

        // Make sure deleting an element invalidates caches.
        $elements->deleteElement($user);
        $this->assertFalse($gql->getCachedResult($cacheKey));

        // Reset
        $gql->setCachedResult($cacheKey, $cacheValue);
        $this->assertEquals($gql->getCachedResult($cacheKey), $cacheValue);

        // Make sure setting anything in project config invalidates caches.
        Craft::$app->getProjectConfig()->set('test.value', true);
        $this->assertFalse($gql->getCachedResult($cacheKey));
    }

    /**
     * Test all Gql Token operations.
     *
     * @throws \yii\base\Exception
     */
    public function testTokenOperations()
    {
        $gql = Craft::$app->getGql();

        $accessToken = StringHelper::randomString();
        $tokenName = StringHelper::randomString(15);

        $token = new GqlToken([
            'name' => $tokenName,
            'accessToken' => $accessToken,
            'enabled' => true,
        ]);

        $gql->saveToken($token);

        // Test fetching token
        $this->assertEquals($gql->getTokenById($token->id)->uid, $token->uid);
        $this->assertEquals($gql->getTokenByUid($token->uid)->id, $token->id);
        $this->assertEquals($gql->getTokenByAccessToken($token->accessToken)->id, $token->id);
        $this->assertEquals($gql->getTokenByName($token->name)->id, $token->id);

        // Test fetching all tokens
        $allSchemas = $gql->getTokens();
        $this->assertNotEmpty($allSchemas);

        // Test public token doesn't exists
        $this->tester->expectThrowable(InvalidArgumentException::class, function() use ($gql) {
            $publicToken = $gql->getTokenByAccessToken(GqlToken::PUBLIC_TOKEN);
        });

        // Test fetching public schema creates public token
        $publicSchema = $gql->getPublicSchema();
        $publicToken = $gql->getTokenByAccessToken(GqlToken::PUBLIC_TOKEN);
        $this->assertEquals($publicToken->accessToken, GqlToken::PUBLIC_TOKEN);

        // Test deleting
        $gql->deleteTokenById($token->id);
        $this->assertNull($gql->getTokenById($token->id));
    }

    /**
     * Test all Gql Schema operations.
     *
     * @throws \yii\base\Exception
     */
    public function testSchemaOperations()
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
        $this->assertEquals($gql->getSchemaById($schemaId)->uid, $schemaUid);
        $this->assertEquals($gql->getSchemaByUid($schemaUid)->id, $schemaId);

        // Test fetching all schemas
        $allSchemas = Craft::$app->getGql()->getSchemas();
        $this->assertNotEmpty($allSchemas);

        // Test deleting
        $gql->deleteSchemaById($schemaId);
        $this->assertNull($gql->getSchemaById($schemaId));
    }
}
