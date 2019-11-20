<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\services;

use Codeception\Test\Unit;
use Craft;
use craft\elements\User;
use craft\errors\GqlException;
use craft\events\ExecuteGqlQueryEvent;
use craft\events\RegisterGqlDirectivesEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\TypeLoader;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use craft\services\Gql;
use craft\test\mockclasses\gql\MockDirective;
use craft\test\mockclasses\gql\MockType;
use GraphQL\Type\Definition\ObjectType;
use yii\base\Event;

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

    // Tests
    // =========================================================================

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
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function (RegisterGqlQueriesEvent $event) {
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
     * Test schema validation by adding an invalid query.
     */
    public function testValidatingSchema()
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function (RegisterGqlQueriesEvent $event) {
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

        Event::on(Gql::class, Gql::EVENT_BEFORE_EXECUTE_GQL_QUERY, function (ExecuteGqlQueryEvent $event) {
            $event->result = ['data' => 'override'];
        });

        $result = $gql->executeQuery($schema, '{ping}');
        $this->assertEquals(['data' => 'override'], $result);

        Event::on(Gql::class, Gql::EVENT_AFTER_EXECUTE_GQL_QUERY, function (ExecuteGqlQueryEvent $event) {
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
            'setCachedResult' => function ($key, $value) use (&$cache, $cacheKey) {$cache[$cacheKey] = $value; },
        ]);

        Craft::$app->getConfig()->getGeneral()->enableGraphQlCaching = true;

        $schema = $gql->getPublicSchema();

        $this->assertArrayNotHasKey($cacheKey, $cache);
        $result = $gql->executeQuery($schema, '{ping}');
        $this->assertEquals($cache[$cacheKey], $result);
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
        $cacheValue = 'testValue';
        $gql->setCachedResult($cacheKey, $cacheValue);

        $schema = new GqlSchema([
            'name' => StringHelper::randomString(15),
            'accessToken' => StringHelper::randomString(15),
            'enabled' => true,
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
     * Test adding custom directives to schema
     */
    public function testRegisteringDirective()
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_DIRECTIVES, function (RegisterGqlDirectivesEvent $event) {
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
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_TYPES, function (RegisterGqlTypesEvent $event) {
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
        $this->tester->expectException(GqlException::class, function () use ($typeName) {
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

        $token = new GqlSchema(['id' => random_int(1, 1000), 'name' => 'Something', 'enabled' => true, 'scope' => ['usergroups.everyone:read']]);
        $schema = $gqlService->getSchemaDef($token);

        $gqlService->flushCaches();

        $token = new GqlSchema(['id' => random_int(1, 1000), 'name' => 'Something', 'enabled' => true, 'scope' => ['volumes.someVolume:read']]);
        $this->assertNotEquals($schema, $gqlService->getSchemaDef($token));
    }

    /**
     * Test if permission list is being generated
     */
    public function testPermissionListGenerated()
    {
        $this->assertNotEmpty(Craft::$app->getGql()->getAllPermissions());
    }

    /**
     * Test all the schema operations
     * @throws \yii\base\Exception
     */
    public function testSchemaOperations()
    {
        $gql = Craft::$app->getGql();
        $gql->invalidateCaches();

        $accessToken = StringHelper::randomString();

        $schema = new GqlSchema([
            'name' => StringHelper::randomString(15),
            'accessToken' => $accessToken,
            'enabled' => true,
            'scope' => []
        ]);

        $gql->saveSchema($schema);

        // Test fetching schema
        $this->assertEquals($gql->getSchemaById($schema->id)->uid, $schema->uid);
        $this->assertEquals($gql->getSchemaByUid($schema->uid)->id, $schema->id);
        $this->assertEquals($gql->getSchemaByAccessToken($schema->accessToken)->id, $schema->id);

        // Test fetching all schemas and existance of public schema
        $allSchemas = Craft::$app->getGql()->getSchemas();
        $this->assertNotEmpty($allSchemas);
        $this->assertEquals($allSchemas[0]->accessToken, GqlSchema::PUBLIC_TOKEN);

        // Test deleting
        $gql->deleteSchemaById($schema->id);
        $this->assertNull($gql->getSchemaById($schema->id));
    }
}
