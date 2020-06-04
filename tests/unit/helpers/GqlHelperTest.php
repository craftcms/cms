<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\helpers;

use Codeception\Test\Unit;
use Craft;
use craft\errors\GqlException;
use craft\gql\arguments\elements\Asset as AssetArguments;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\resolvers\elements\Asset as AssetResolver;
use craft\helpers\Gql as GqlHelper;
use craft\models\GqlSchema;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;

class GqlHelperTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * Test Schema helper methods.
     *
     * @dataProvider schemaPermissionDataProvider
     *
     * @param array $permissionSet list of permissions the active schema should have
     * @param string $permission A single permission to check
     * @param string $scope Permission check against this scope must return true
     * @param string $failingScope Permission check against this scope must return false
     * @param bool $failAll Whether all tests should fail.
     *
     * @throws GqlException
     * @throws \yii\base\Exception
     */
    public function testSchemaHelper($permissionSet, $permission, $scope, $failingScope, $failAll = false)
    {
        $this->_setSchemaWithPermissions($permissionSet);

        // Schema awareness
        if (!$failAll) {
            $this->assertTrue(GqlHelper::canSchema($permission, $scope));
            $this->assertFalse(GqlHelper::canSchema($permission, $failingScope));
            $this->assertTrue(GqlHelper::isSchemaAwareOf($permission));
        } else {
            $this->assertFalse(GqlHelper::canSchema($permission, $scope));
            $this->assertFalse(GqlHelper::canSchema($permission, $failingScope));
            $this->assertFalse(GqlHelper::isSchemaAwareOf($permission));
        }
    }

    /**
     * Test permission extraction from schema.
     *
     * @dataProvider schemaPermissionDataProviderForExtraction
     *
     * @param array $permissionSet list of permissions the schems should have
     */
    public function testSchemaPermissionExtraction($permissionSet, $expectedPairs)
    {
        $this->_setSchemaWithPermissions($permissionSet);
        $this->assertEquals($expectedPairs, GqlHelper::extractAllowedEntitiesFromSchema());
    }

    /**
     * Test various helper methods handling errors nicely if no schema set.
     */
    public function testVariousErrors()
    {
        // Null the schema
        Craft::$app->getGql()->setActiveSchema(null);

        $this->assertFalse(GqlHelper::isSchemaAwareOf('something'));
        $this->assertFalse(GqlHelper::canSchema('something'));

        $result = GqlHelper::extractAllowedEntitiesFromSchema();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test whether `canQuery*` functions work correctly
     *
     * @throws \yii\base\Exception
     */
    public function testSchemaQueryAbility()
    {
        $permissionSet = [
            'usergroups.allUsers:read',
            'globalsets.someSet:read',
            'entrytypes.someEntry:read',
            'sections.someSection:read'
        ];

        $this->_setSchemaWithPermissions($permissionSet);

        $this->assertTrue(GqlHelper::canQueryEntries());
        $this->assertTrue(GqlHelper::canQueryGlobalSets());
        $this->assertTrue(GqlHelper::canQueryUsers());
        $this->assertFalse(GqlHelper::canQueryAssets());
        $this->assertFalse(GqlHelper::canQueryCategories());
        $this->assertFalse(GqlHelper::canQueryTags());
    }

    /**
     * Test if a union type is successfully created
     */
    public function testUnionTypes()
    {
        $unionType = GqlHelper::getUnionType('someUnion', ['one', 'two'], function() {
            return 'one';
        });
        $this->assertInstanceOf(UnionType::class, $unionType);
    }

    /**
     * Test if a full access schema is created correctly.
     */
    public function testFullAccessSchema()
    {
        $schema = GqlHelper::createFullAccessSchema();

        // Not very realistic to test *everything* without duplicating logic in the helper method
        $this->assertNotEmpty($schema->scope);
    }

    /**
     * Test if entity actions are extracted correctly
     *
     * @dataProvider actionExtractionDataProvider
     */
    public function testEntityActionExtraction($scope, $entity, $result)
    {
        $this->_setSchemaWithPermissions($scope);

        $this->assertEquals($result, GqlHelper::extractEntityAllowedActions($entity));
    }

    /**
     * Test GQL types correctly wrapped in NonNull type.
     * @param $input
     * @param $expected
     * @dataProvider wrapInNonNullProvider
     */
    public function testWrapInNonNull($input, $expected)
    {
        $this->assertEquals($expected, GqlHelper::wrapInNonNull($input));
    }

    public function wrapInNonNullProvider()
    {
        $typeDef = [
            'name' => 'mock',
            'type' => Type::listOf(Type::string()),
            'args' => []
        ];

        $nonNulledTypeDef = [
            'name' => 'mock',
            'type' => Type::nonNull(Type::listOf(Type::string())),
            'args' => []
        ];

        return [
            [Type::boolean(), Type::nonNull(Type::boolean())],
            [Type::string(),Type::nonNull(Type::string())],
            [Type::id(),Type::nonNull(Type::id())],
            [Type::nonNull(Type::int()),Type::nonNull(Type::int())],
            [$typeDef, $nonNulledTypeDef],
        ];
    }


    public function actionExtractionDataProvider()
    {
        return [
            [
                [
                    'entity-one:read',
                    'entity-two:read',
                    'entity-two:write',
                    'entity-two:observe',
                ],
                'entity-one',
                ['read'],
            ],
            [
                [
                    'entity-one:read',
                    'entity-two:read',
                    'entity-two:write',
                    'entity-two:observe',
                ],
                'entity-two',
                ['read', 'write', 'observe'],
            ],            [
                [
                    'entity-one:read',
                    'entity-two:read',
                    'entity-two:read',
                    'entity-two:observe',
                ],
                'entity-two',
                ['read', 'observe'],
            ],
            [
                [
                    'entity-one:read',
                    'entity-two:read',
                    'entity-two:write',
                    'entity-two:observe',
                ],
                'entity-three',
                [],
            ],
        ];
    }

    public function schemaPermissionDataProvider()
    {
        return [
            [
                [
                    'usergroups.allUsers:read',
                    'volumes.someVolume:read',
                    'globalsets.someSet:read',
                    'entrytypes.someEntry:read',
                    'sections.someSection:read'
                ],
                'volumes.someVolume',
                'read',
                'write'
            ],
            [
                [
                    'usergroups.allUsers:write',
                    'volumes.someVolume:read',
                    'volumes.someVolume:write',
                    'globalsets.someSet:write',
                    'entrytypes.someEntry:write',
                    'sections.someSection:write'
                ],
                'volumes.someVolume',
                'write',
                'delete'
            ],
            [
                [],
                'volumes.someVolume',
                'write',
                'delete',
                true
            ],
        ];
    }

    public function schemaPermissionDataProviderForExtraction()
    {
        return [
            [
                [
                    'usergroups.allUsers:read',
                    'volumes.someVolume:read',
                    'globalsets.someSet:read',
                    'entrytypes.someEntry:read',
                    'sections.someSection:read'
                ],
                [
                    'usergroups' => ['allUsers'],
                    'volumes' => ['someVolume'],
                    'globalsets' => ['someSet'],
                    'entrytypes' => ['someEntry'],
                    'sections' => ['someSection'],
                ]
            ],
            [
                [
                    'usergroups.allUsers:read',
                    'usergroups.otherGroup:read',
                ],
                [
                    'usergroups' => ['allUsers', 'otherGroup'],
                ]
            ], [
                [
                    'usergroups.allUsers:read',
                    'usergroups.otherGroup:write',
                ],
                [
                    'usergroups' => ['allUsers'],
                ]
            ],
            [
                [
                    'usergroups.allUsers:write',
                    'volumes.someVolume:write',
                    'globalsets.someSet:write',
                    'entrytypes.someEntry:write',
                    'sections.someSection:write'
                ],
                []
            ],
            [
                [
                    'usergroups.allUsers:write',
                    'volumes.someVolume:write',
                    'globalsets.someSet:write',
                    'entrytypes.someEntry:read',
                    'sections.someSection:write'
                ],
                [
                    'entrytypes' => ['someEntry'],
                ]
            ],
            [
                [],
                []
            ],
        ];
    }

    /**
     * Set a schema with permission set
     */
    public function _setSchemaWithPermissions($scopeSet)
    {
        $gqlService = Craft::$app->getGql();
        $schema = new GqlSchema(['id' => uniqid(), 'name' => 'Something', 'scope' => $scopeSet]);
        $gqlService->setActiveSchema($schema);
    }
}
