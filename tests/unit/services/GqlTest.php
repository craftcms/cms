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
use craft\events\RegisterGqlDirectivesEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\TypeLoader;
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
    }

    protected function _after()
    {
    }

    // Tests
    // =========================================================================

    /**
     * Test schema creation without token.
     *
     * @expectedException \craft\errors\GqlException
     * @expectedExceptionMessage No schema is active.
     */
    public function testCreatingSchemaFail()
    {
        $gqlService = Craft::$app->getGql();
        $gqlService->setActiveSchema(null);
        $gqlService->getSchemaDef();
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

        $mockType = Craft::$app->getGql()->getSchemaDef()->getType(MockType::getName());
        $this->assertInstanceOf('GraphQL\Type\Definition\ObjectType', $mockType);
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
}
