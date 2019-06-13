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
    }

    protected function _after()
    {
    }

    // Tests
    // =========================================================================

    /**
     * Test schema creation.
     */
    public function testCreateSchema()
    {
        $schema = Craft::$app->getGql()->getSchema();
        $this->assertInstanceOf('GraphQL\Type\Schema', $schema);
    }

    /**
     * Test adding custom queries to schema
     */
    public function testRegisterQuery()
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function (RegisterGqlQueriesEvent $event) {
            $event->queries['mockQuery'] = [
                'type' => [],
                'args' => [],
                'resolve' => []
            ];
        });

        $queries = Craft::$app->getGql()->getSchema()->getQueryType()->getFields();
        $this->assertArrayHasKey('mockQuery', $queries);
    }

    /**
     * Test schema validation by adding an invalid query.
     */
    public function testValidateSchema()
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function (RegisterGqlQueriesEvent $event) {
            $event->queries['mockQuery'] = [
                'type' => 'no bueno'
            ];
        });

        $this->expectException('craft\errors\GqlException');
        Craft::$app->getGql()->getSchema(true);
    }

    /**
     * Test adding custom directives to schema
     * // todo: stub instead of mock?
     */
    public function testRegisterDirective()
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_DIRECTIVES, function (RegisterGqlDirectivesEvent $event) {
            $event->directives[] = MockDirective::class;
        });

        $directive = Craft::$app->getGql()->getSchema()->getDirective(MockDirective::getName());
        $this->assertInstanceOf('GraphQL\Type\Definition\Directive', $directive);
    }

    /**
     * Test adding custom types to schema
     */
    public function testRegisterType()
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_TYPES, function (RegisterGqlTypesEvent $event) {
            $event->types[] = MockType::class;
        });

        $mockType = Craft::$app->getGql()->getSchema()->getType(MockType::getName());
        $this->assertInstanceOf('GraphQL\Type\Definition\ObjectType', $mockType);
    }

    /**
     * Test if flushing works.
     */
    public function testFlushing()
    {
        // Generate types by creating the interface.
        UserInterface::getType();
        $typeName = User::getGqlTypeNameByContext(null);

        $this->assertNotFalse(GqlEntityRegistry::getEntity($typeName));
        $this->assertInstanceOf(ObjectType::class, TypeLoader::loadType($typeName));

        Craft::$app->getGql()->flushCaches();

        $this->assertFalse(GqlEntityRegistry::getEntity($typeName));
        $this->tester->expectException(GqlException::class, function () use ($typeName) {
            TypeLoader::loadType($typeName);
        });
    }
}