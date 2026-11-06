<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\events\RegisterUserPermissionsEvent;
use craft\mcp\capabilities\UserPermissions;
use craft\mcp\McpActor;
use craft\services\UserPermissions as UserPermissionService;
use craft\test\TestCase;
use crafttests\fixtures\UserGroupsFixture;
use Mcp\Exception\ToolCallException;
use yii\base\Event;

/** Group permission tools must not lose stored permissions whose provider isn’t registered, as over stdio. */
class UserPermissionsCapabilityTest extends TestCase
{
    public function _fixtures(): array
    {
        return ['user-groups' => ['class' => UserGroupsFixture::class]];
    }

    public function testStoredGroupPermissionsWithUnregisteredProviderAreKept(): void
    {
        Craft::$app->edition = CmsEdition::Pro;
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
        $service = Craft::$app->getUserPermissions();
        $handler = function(RegisterUserPermissionsEvent $event) {
            $event->permissions[] = ['heading' => 'Probe', 'permissions' => ['probe:view' => ['label' => 'View']]];
        };
        Event::on(UserPermissionService::class, UserPermissionService::EVENT_REGISTER_PERMISSIONS, $handler);
        $service->reset();
        $service->saveGroupPermissions(1000, ['accessCp', 'probe:view']);

        // Like a plugin that only registers its permissions for control panel requests
        Event::off(UserPermissionService::class, UserPermissionService::EVENT_REGISTER_PERMISSIONS, $handler);
        $service->reset();

        try {
            $capability = new UserPermissions(new McpActor(), $service, Craft::$app->getUsers(), Craft::$app->getUserGroups());
            $current = $capability->getGroup(groupId: 1000)['permissions'];

            self::assertSame(
                ['accesscp', 'accesssitewhensystemisoff', 'probe:view'],
                $capability->setGroup([...$current, 'accessSiteWhenSystemIsOff'], groupId: 1000)['permissions'],
            );

            try {
                $capability->updateGroup(add: ['probe:edit'], groupId: 1000);
                self::fail('Adding an unknown permission was not rejected.');
            } catch (ToolCallException $e) {
                self::assertStringContainsString('probe:edit', $e->getMessage());
            }

            try {
                $capability->updateGroup(remove: ['accessCpp'], groupId: 1000);
                self::fail('Removing an unknown permission was not rejected.');
            } catch (ToolCallException $e) {
                self::assertStringContainsString('accessCpp', $e->getMessage());
            }

            self::assertSame(
                ['accesscp', 'probe:view', 'viewusers'],
                $capability->updateGroup(add: ['viewUsers'], remove: ['accessSiteWhenSystemIsOff'], groupId: 1000)['permissions'],
            );
            self::assertSame(
                ['accesscp', 'viewusers'],
                $capability->updateGroup(remove: ['probe:view'], groupId: 1000)['permissions'],
            );
        } finally {
            Craft::$app->getUser()->setIdentity(null);
        }
    }
}
