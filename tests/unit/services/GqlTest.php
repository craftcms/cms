<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\services;

use Codeception\Test\Unit;
use Codeception\Util\Fixtures;
use Craft;
use craft\elements\User;
use craft\errors\GqlException;
use craft\events\RegisterGqlDirectivesEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\TypeLoader;
use craft\models\GqlToken;
use craft\services\Gql;
use craft\test\Fixture;
use craft\test\mockclasses\gql\MockDirective;
use craft\test\mockclasses\gql\MockType;
use crafttests\fixtures\GqlTokensFixture;
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
    public function testCreatingSchema()
    {
        $schema = Craft::$app->getGql()->getSchema();
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

        $queries = Craft::$app->getGql()->getSchema()->getQueryType()->getFields();
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
        Craft::$app->getGql()->getSchema(null, true);
    }

    /**
     * Test adding custom directives to schema
     */
    public function testRegisteringDirective()
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
    public function testRegisteringType()
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
    public function testFlushingCaches()
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

    /**
     * Test if we're able to save and retrieve tokens.
     *
     * @throws \yii\base\Exception
     */
    public function testTokenSaveAndRetrieval()
    {
        $gqlService = Craft::$app->getGql();

        $token = new GqlToken(['name' => 'Something', 'enabled' => true]);
        $gqlService->saveToken($token);

        $this->assertEquals($token->id, $gqlService->getTokenByAccessToken($token->accessToken)->id);
        $this->assertEquals($token->accessToken, $gqlService->getTokenById($token->id)->accessToken);

        $token = new GqlToken(['name' => 'Different', 'enabled' => true]);
        $gqlService->saveToken($token);

        $tokenList = $gqlService->getTokens();
        $this->assertCount(2, $tokenList);
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

        $token = new GqlToken(['name' => 'Something', 'enabled' => true, 'permissions' => ['usergroups.allUsers:read']]);
        $gqlService->saveToken($token);
        $schema = $gqlService->getSchema($token);

        $gqlService->flushCaches();

        $token = new GqlToken(['name' => 'Something', 'enabled' => true, 'permissions' => ['volumes.someVolume:read']]);
        $gqlService->saveToken($token, true);
        $this->assertNotEquals($schema, $gqlService->getSchema($token));
    }

    /**
     * Test if permission list is being generated
     */
    public function testPermissionListGenerated()
    {
        $this->assertNotEmpty(Craft::$app->getGql()->getAllPermissions());
    }
}