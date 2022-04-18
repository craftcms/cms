<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Craft;
use craft\errors\GqlException;
use craft\helpers\Gql as GqlHelper;
use craft\models\GqlSchema;
use craft\test\TestCase;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use yii\base\Exception;

class GqlHelperTest extends TestCase
{
    /**
     * Test Schema helper methods.
     *
     * @dataProvider schemaPermissionDataProvider
     * @param array $permissionSet list of permissions the active schema should have
     * @param string $permission A single permission to check
     * @param string $scope Permission check against this scope must return true
     * @param string $failingScope Permission check against this scope must return false
     * @param bool $failAll Whether all tests should fail.
     * @throws GqlException
     * @throws Exception
     */
    public function testSchemaHelper(array $permissionSet, string $permission, string $scope, string $failingScope, bool $failAll = false): void
    {
        $this->_setSchemaWithPermissions($permissionSet);

        // Schema awareness
        if (!$failAll) {
            self::assertTrue(GqlHelper::canSchema($permission, $scope));
            self::assertFalse(GqlHelper::canSchema($permission, $failingScope));
            self::assertTrue(GqlHelper::isSchemaAwareOf($permission));
        } else {
            self::assertFalse(GqlHelper::canSchema($permission, $scope));
            self::assertFalse(GqlHelper::canSchema($permission, $failingScope));
            self::assertFalse(GqlHelper::isSchemaAwareOf($permission));
        }
    }

    /**
     * Test permission extraction from schema.
     *
     * @dataProvider schemaPermissionDataProviderForExtraction
     * @param array $permissionSet list of permissions the schemas should have
     * @param array $expectedPairs
     */
    public function testSchemaPermissionExtraction(array $permissionSet, array $expectedPairs): void
    {
        $this->_setSchemaWithPermissions($permissionSet);
        self::assertEquals($expectedPairs, GqlHelper::extractAllowedEntitiesFromSchema());
    }

    /**
     * Test various helper methods handling errors nicely if no schema set.
     */
    public function testVariousErrors(): void
    {
        // Null the schema
        Craft::$app->getGql()->setActiveSchema(null);

        self::assertFalse(GqlHelper::isSchemaAwareOf('something'));
        self::assertFalse(GqlHelper::canSchema('something'));

        $result = GqlHelper::extractAllowedEntitiesFromSchema();
        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * Test whether `canQuery*` functions work correctly
     *
     * @throws Exception
     */
    public function testSchemaQueryAbility(): void
    {
        $permissionSet = [
            'usergroups.allUsers:read',
            'globalsets.someSet:read',
            'entrytypes.someEntry:read',
            'sections.someSection:read',
        ];

        $this->_setSchemaWithPermissions($permissionSet);

        self::assertTrue(GqlHelper::canQueryEntries());
        self::assertTrue(GqlHelper::canQueryGlobalSets());
        self::assertTrue(GqlHelper::canQueryUsers());
        self::assertFalse(GqlHelper::canQueryAssets());
        self::assertFalse(GqlHelper::canQueryCategories());
        self::assertFalse(GqlHelper::canQueryTags());
    }

    /**
     * Test if a union type is successfully created
     */
    public function testUnionTypes(): void
    {
        $unionType = GqlHelper::getUnionType('someUnion', ['one', 'two'], function() {
            return 'one';
        });
        self::assertInstanceOf(UnionType::class, $unionType);
    }

    /**
     * Test if a full access schema is created correctly.
     */
    public function testFullAccessSchema(): void
    {
        $schema = GqlHelper::createFullAccessSchema();

        // Not very realistic to test *everything* without duplicating logic in the helper method
        self::assertNotEmpty($schema->scope);
    }

    /**
     * Test if entity actions are extracted correctly
     *
     * @dataProvider actionExtractionDataProvider
     * @param array $scope
     * @param string $entity
     * @param array $result
     */
    public function testEntityActionExtraction(array $scope, string $entity, array $result): void
    {
        $this->_setSchemaWithPermissions($scope);

        self::assertEquals($result, GqlHelper::extractEntityAllowedActions($entity));
    }

    /**
     * Test GQL types correctly wrapped in NonNull type.
     *
     * @param mixed $input
     * @param mixed $expected
     * @dataProvider wrapInNonNullProvider
     */
    public function testWrapInNonNull(mixed $input, mixed $expected): void
    {
        self::assertEquals($expected, GqlHelper::wrapInNonNull($input));
    }

    public function wrapInNonNullProvider(): array
    {
        $typeDef = [
            'name' => 'mock',
            'type' => Type::listOf(Type::string()),
            'args' => [],
        ];

        $nonNulledTypeDef = [
            'name' => 'mock',
            'type' => Type::nonNull(Type::listOf(Type::string())),
            'args' => [],
        ];

        return [
            [Type::boolean(), Type::nonNull(Type::boolean())],
            [Type::string(), Type::nonNull(Type::string())],
            [Type::id(), Type::nonNull(Type::id())],
            [Type::nonNull(Type::int()), Type::nonNull(Type::int())],
            [$typeDef, $nonNulledTypeDef],
        ];
    }


    public function actionExtractionDataProvider(): array
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
            ], [
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

    public function schemaPermissionDataProvider(): array
    {
        return [
            [
                [
                    'usergroups.allUsers:read',
                    'volumes.someVolume:read',
                    'globalsets.someSet:read',
                    'entrytypes.someEntry:read',
                    'sections.someSection:read',
                ],
                'volumes.someVolume',
                'read',
                'write',
            ],
            [
                [
                    'usergroups.allUsers:write',
                    'volumes.someVolume:read',
                    'volumes.someVolume:write',
                    'globalsets.someSet:write',
                    'entrytypes.someEntry:write',
                    'sections.someSection:write',
                ],
                'volumes.someVolume',
                'write',
                'delete',
            ],
            [
                [],
                'volumes.someVolume',
                'write',
                'delete',
                true,
            ],
        ];
    }

    public function schemaPermissionDataProviderForExtraction(): array
    {
        return [
            [
                [
                    'usergroups.allUsers:read',
                    'volumes.someVolume:read',
                    'globalsets.someSet:read',
                    'entrytypes.someEntry:read',
                    'sections.someSection:read',
                ],
                [
                    'usergroups' => ['allUsers'],
                    'volumes' => ['someVolume'],
                    'globalsets' => ['someSet'],
                    'entrytypes' => ['someEntry'],
                    'sections' => ['someSection'],
                ],
            ],
            [
                [
                    'usergroups.allUsers:read',
                    'usergroups.otherGroup:read',
                ],
                [
                    'usergroups' => ['allUsers', 'otherGroup'],
                ],
            ], [
                [
                    'usergroups.allUsers:read',
                    'usergroups.otherGroup:write',
                ],
                [
                    'usergroups' => ['allUsers'],
                ],
            ],
            [
                [
                    'usergroups.allUsers:write',
                    'volumes.someVolume:write',
                    'globalsets.someSet:write',
                    'entrytypes.someEntry:write',
                    'sections.someSection:write',
                ],
                [],
            ],
            [
                [
                    'usergroups.allUsers:write',
                    'volumes.someVolume:write',
                    'globalsets.someSet:write',
                    'entrytypes.someEntry:read',
                    'sections.someSection:write',
                ],
                [
                    'entrytypes' => ['someEntry'],
                ],
            ],
            [
                [],
                [],
            ],
        ];
    }

    /**
     * Set a schema with permission set.
     *
     * @param array $scopeSet
     */
    public function _setSchemaWithPermissions(array $scopeSet)
    {
        $gqlService = Craft::$app->getGql();
        $schema = new GqlSchema(['id' => random_int(1, 1000), 'name' => 'Something', 'scope' => $scopeSet]);
        $gqlService->setActiveSchema($schema);
    }
}
