<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\elements\User;
use craft\errors\WrongEditionException;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use craft\test\TestCase;
use crafttests\fixtures\GlobalSetFixture;
use crafttests\fixtures\SectionsFixture;
use crafttests\fixtures\SitesFixture;
use crafttests\fixtures\UserGroupsFixture;
use crafttests\fixtures\UsersFixture;
use crafttests\fixtures\VolumesFixture;
use UnitTester;
use yii\db\Exception as YiiDbException;

/**
 * Unit tests for the User permissions service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UserPermissionsTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var UserPermissions
     */
    protected $userPermissions;

    /**
     * @var User
     */
    protected $activeUser;

    public function _fixtures(): array
    {
        return [
            'user-groups' => [
                'class' => UserGroupsFixture::class
            ],
            'users' => [
                'class' => UsersFixture::class
            ],
            'sites' => [
                'class' => SitesFixture::class
            ],
            'sections' => [
                'class' => SectionsFixture::class
            ],
            'globals' => [
                'class' => GlobalSetFixture::class,
            ],
            'volumes' => [
                'class' => VolumesFixture::class
            ]
        ];
    }

    /**
     *
     */
    public function testGetAllPermissions()
    {
        $permissions = [];

        $this->tester->expectEvent(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function() use (&$permissions) {
            $permissions = $this->userPermissions->getAllPermissions();
        }, RegisterUserPermissionsEvent::class);

        // Just check for the main keys.
        $this->assertArrayHasKey('General', $permissions);
        $this->assertArrayHasKey('Sites', $permissions);
        $this->assertArrayHasKey('Section - Single', $permissions);
        $this->assertArrayHasKey('Section - Test 1', $permissions);
        $this->assertArrayHasKey('Global Sets', $permissions);
        $this->assertArrayHasKey('Volume - Test volume 1', $permissions);
        $this->assertArrayHasKey('Utilities', $permissions);
    }

    /**
     * @throws WrongEditionException
     */
    public function testDoesGroupHavePermission()
    {
        Craft::$app->setEdition(Craft::Pro);

        $this->assertFalse(
            $this->userPermissions->doesGroupHavePermission('1000', 'accessCp')
        );

        $this->userPermissions->saveGroupPermissions('1000', ['accessCp']);

        $this->assertTrue(
            $this->userPermissions->doesGroupHavePermission('1000', 'accessCp')
        );

        $this->assertFalse(
            $this->userPermissions->doesGroupHavePermission('1000', 'registerUsers')
        );
        $this->assertFalse(
            $this->userPermissions->doesGroupHavePermission('1000', 'invalidPermission')
        );

        $this->userPermissions->saveGroupPermissions('1000', ['assignUserPermissions', 'accessCp']);
        $this->assertFalse(
            $this->userPermissions->doesGroupHavePermission('1000', 'assignUserPermissions')
        );
        $this->assertTrue(
            $this->userPermissions->doesGroupHavePermission('1000', 'accessCp')
        );
    }

    /**
     * @throws YiiDbException
     * @throws WrongEditionException
     * @todo Tests for _filterOrphanedPermissions - use codecov.io for this.
     */
    public function testDoesUserHavePermission()
    {
        Craft::$app->setEdition(Craft::Pro);
        $this->userPermissions->saveGroupPermissions('1000', ['accessCp']);

        $user = User::find()
            ->admin(false)
            ->one();
        Craft::$app->getUsers()->assignUserToGroups($user->id, ['1000']);

        $this->assertTrue(
            $this->userPermissions->doesUserHavePermission($user->id, 'accessCp')
        );

        $this->assertFalse(
            $this->userPermissions->doesUserHavePermission($user->id, 'invalidPermission')
        );

        $this->userPermissions->saveUserPermissions($user->id, ['editUsers']);
        $this->assertTrue(
            $this->userPermissions->doesUserHavePermission($user->id, 'editUsers')
        );
        $this->assertTrue(
            $this->userPermissions->doesUserHavePermission($user->id, 'accessCp')
        );
    }

    /**
     * @throws WrongEditionException
     */
    public function testPermissionGet()
    {
        // Setup user and craft
        Craft::$app->setEdition(Craft::Pro);
        $this->userPermissions->saveGroupPermissions('1001', ['utility:php-info']);
        $this->userPermissions->saveGroupPermissions('1000', ['accessCp', 'utility:updates']);

        $user = User::find()
            ->admin(false)
            ->one();

        Craft::$app->getUsers()->assignUserToGroups($user->id, ['1000', '1001']);

        $this->assertCount(3, $this->userPermissions->getPermissionsByUserId($user->id));
        $this->assertCount(
            3,
            $this->userPermissions->getGroupPermissionsByUserId($user->id)
        );

        $this->assertCount(
            2,
            $this->userPermissions->getPermissionsByGroupId('1000')
        );
    }

    /**
     * @throws WrongEditionException
     */
    public function testChangedGroupPermissions()
    {
        // Setup user and craft
        Craft::$app->setEdition(Craft::Pro);
        $this->userPermissions->saveGroupPermissions('1000', ['accessCp']);

        $user = User::find()
            ->admin(false)
            ->one();
        Craft::$app->getUsers()->assignUserToGroups($user->id, ['1000']);

        $this->assertTrue($this->userPermissions->doesUserHavePermission($user->id, 'accessCp'));
        $this->assertFalse($this->userPermissions->doesUserHavePermission($user->id, 'utility:updates'));

        // Add a permission and check again.
        $this->userPermissions->saveGroupPermissions('1000', ['accessCp', 'utility:updates']);
        $this->assertTrue($this->userPermissions->doesUserHavePermission($user->id, 'accessCp'));
        $this->assertFalse($this->userPermissions->doesUserHavePermission($user->id, 'utility:updates'));
    }


    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();

        $this->userPermissions = Craft::$app->getUserPermissions();
    }
}
