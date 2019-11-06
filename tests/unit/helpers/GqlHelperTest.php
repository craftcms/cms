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
use craft\helpers\Gql as GqlHelper;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;

class GqlHelperTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _before()
    {
        // Fake out token save that would occur on setting it.
        $this->tester->mockMethods(
            Craft::$app,
            'gql',
            ['saveSchema' => function () { return true;}]
        );
    }

    // Tests
    // =========================================================================

    /**
     * Test Token helper methods.
     *
     * @dataProvider tokenPermissionDataProvider
     *
     * @param array $permissionSet list of permissions the token should have
     * @param string $permission A single permission to check
     * @param string $scope Permission check against this scope must return true
     * @param string $failingScope Permission check against this scope must return false
     * @param bool $failAll Whether all tests should fail.
     *
     * @throws GqlException
     * @throws \yii\base\Exception
     */
    public function testTokenHelper($permissionSet, $permission, $scope, $failingScope, $failAll = false)
    {
        $this->_setTokenWithPermissions($permissionSet);

        // Token awareness
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
     * Test permission extraction from token.
     *
     * @dataProvider tokenPermissionDataProviderForExtraction
     *
     * @param array $permissionSet list of permissions the token should have
     *
     * @throws GqlException
     * @throws \yii\base\Exception
     */
    public function testTokenPermissionExtraction($permissionSet, $expectedPairs)
    {
        $this->_setTokenWithPermissions($permissionSet);
        $this->assertEquals($expectedPairs, GqlHelper::extractAllowedEntitiesFromSchema());
    }

    public function tokenPermissionDataProvider()
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

    public function tokenPermissionDataProviderForExtraction()
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
            ],[
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
     * Set a token with permission set
     */
    public function _setTokenWithPermissions($scopeSet)
    {
        $gqlService = Craft::$app->getGql();
        $schema = new GqlSchema(['id' => uniqid(), 'name' => 'Something', 'enabled' => true, 'scope' => $scopeSet]);
        $gqlService->setActiveSchema($schema);
    }
}
