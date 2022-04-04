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
use craft\helpers\ArrayHelper;
use craft\services\UserPermissions;
use craft\test\TestCase;
use crafttests\fixtures\GlobalSetFixture;
use crafttests\fixtures\SectionsFixture;
use crafttests\fixtures\SitesFixture;
use crafttests\fixtures\UserFixture;
use crafttests\fixtures\UserGroupsFixture;
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
    protected UnitTester $tester;

    /**
     * @var UserPermissions
     */
    protected UserPermissions $userPermissions;

    public function _fixtures(): array
    {
        return [
            'user-groups' => [
                'class' => UserGroupsFixture::class,
            ],
            'users' => [
                'class' => UserFixture::class,
            ],
            'sites' => [
                'class' => SitesFixture::class,
            ],
            'sections' => [
                'class' => SectionsFixture::class,
            ],
            'globals' => [
                'class' => GlobalSetFixture::class,
            ],
            'volumes' => [
                'class' => VolumesFixture::class,
            ],
        ];
    }


    /**
     *
     */
    public function testGetAllPermissions(): void
    {
        $permissions = [];

        $this->tester->expectEvent(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function() use (&$permissions) {
            $permissions = $this->userPermissions->getAllPermissions();
        }, RegisterUserPermissionsEvent::class);

        // Just check for the main group headings.
        $headings = ArrayHelper::getColumn($permissions, 'heading');
        self::assertContains('General', $headings);
        self::assertContains('Sites', $headings);
        self::assertContains('Section - Single', $headings);
        self::assertContains('Section - Test 1', $headings);
        self::assertContains('Global Sets', $headings);
        self::assertContains('Volume - Test volume 1', $headings);
        self::assertContains('Utilities', $headings);
    }

    /**
     * @throws WrongEditionException
     */
    public function testDoesGroupHavePermission(): void
    {
        self::assertFalse(
            $this->userPermissions->doesGroupHavePermission(1000, 'accessCp')
        );

        $this->userPermissions->saveGroupPermissions(1000, ['accessCp']);

        self::assertTrue(
            $this->userPermissions->doesGroupHavePermission(1000, 'accessCp')
        );

        self::assertFalse(
            $this->userPermissions->doesGroupHavePermission(1000, 'registerUsers')
        );
        self::assertFalse(
            $this->userPermissions->doesGroupHavePermission(1000, 'invalidPermission')
        );

        $this->userPermissions->saveGroupPermissions(1000, ['assignUserPermissions', 'accessCp']);
        self::assertFalse(
            $this->userPermissions->doesGroupHavePermission(1000, 'assignUserPermissions')
        );
        self::assertTrue(
            $this->userPermissions->doesGroupHavePermission(1000, 'accessCp')
        );
    }

    /**
     * @throws YiiDbException
     * @throws WrongEditionException
     * @todo Tests for _filterOrphanedPermissions - use codecov.io for this.
     */
    public function testDoesUserHavePermission(): void
    {
        $this->userPermissions->saveGroupPermissions(1000, ['accessCp']);

        /** @var User $user */
        $user = User::find()
            ->admin(false)
            ->one();
        self::assertNotNull($user);
        Craft::$app->getUsers()->assignUserToGroups($user->id, [1000]);

        self::assertTrue(
            $this->userPermissions->doesUserHavePermission($user->id, 'accessCp')
        );

        self::assertFalse(
            $this->userPermissions->doesUserHavePermission($user->id, 'invalidPermission')
        );

        $this->userPermissions->saveUserPermissions($user->id, ['editUsers']);
        self::assertTrue(
            $this->userPermissions->doesUserHavePermission($user->id, 'editUsers')
        );
        self::assertTrue(
            $this->userPermissions->doesUserHavePermission($user->id, 'accessCp')
        );
    }

    /**
     * @throws WrongEditionException
     */
    public function testPermissionGet(): void
    {
        // Setup user and craft
        $this->userPermissions->saveGroupPermissions(1001, ['utility:php-info']);
        $this->userPermissions->saveGroupPermissions(1000, ['accessCp', 'utility:updates']);

        /** @var User $user */
        $user = User::find()
            ->admin(false)
            ->one();

        self::assertNotNull($user);

        Craft::$app->getUsers()->assignUserToGroups($user->id, [1000, 1001]);

        self::assertCount(3, $this->userPermissions->getPermissionsByUserId($user->id));
        self::assertCount(
            3,
            $this->userPermissions->getGroupPermissionsByUserId($user->id)
        );

        self::assertCount(
            2,
            $this->userPermissions->getPermissionsByGroupId(1000)
        );
    }

    /**
     * @throws WrongEditionException
     */
    public function testChangedGroupPermissions(): void
    {
        // Setup user and craft
        $this->userPermissions->saveGroupPermissions(1000, ['accessCp']);

        /** @var User $user */
        $user = User::find()
            ->admin(false)
            ->one();

        self::assertNotNull($user);

        Craft::$app->getUsers()->assignUserToGroups($user->id, [1000]);

        self::assertTrue($this->userPermissions->doesUserHavePermission($user->id, 'accessCp'));
        self::assertFalse($this->userPermissions->doesUserHavePermission($user->id, 'utility:updates'));

        // Add a permission and check again.
        $this->userPermissions->saveGroupPermissions(1000, ['accessCp', 'utility:updates']);
        self::assertTrue($this->userPermissions->doesUserHavePermission($user->id, 'accessCp'));
        self::assertFalse($this->userPermissions->doesUserHavePermission($user->id, 'utility:updates'));
    }


    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        Craft::$app->setEdition(Craft::Pro);
        Craft::$app->getProjectConfig()->rebuild();
        parent::_before();

        $this->userPermissions = Craft::$app->getUserPermissions();
    }
}
