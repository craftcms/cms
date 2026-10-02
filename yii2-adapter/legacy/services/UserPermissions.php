<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craft\services;

use Craft;
use craft\events\UserGroupPermissionsEvent;
use craft\events\UserPermissionsEvent;
use CraftCms\Cms\Edition\Exceptions\WrongEditionException;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\UserGroupPermissionsSaved;
use CraftCms\Cms\User\Events\UserPermissionsSaved;
use CraftCms\Cms\User\UserPermissions as UserPermissionsService;
use Illuminate\Support\Facades\Event;
use yii\base\Component;

/**
 * User Permissions service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getUserPermissions()|`Craft::$app->getUserPermissions()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see UserPermissionsService} instead.
 */
class UserPermissions extends Component
{
    /**
     * @event RegisterUserPermissionsEvent The event that is triggered when registering user permissions.
     */
    public const EVENT_REGISTER_PERMISSIONS = 'registerPermissions';

    /**
     * @event UserPermissionsEvent The event triggered before saving user permissions.
     *
     * @since 4.3.0
     */
    public const EVENT_AFTER_SAVE_USER_PERMISSIONS = 'afterSaveUserPermissions';

    /**
     * @event UserGroupPermissionsEvent The event triggered before saving group permissions.
     *
     * @since 4.3.0
     */
    public const EVENT_AFTER_SAVE_GROUP_PERMISSIONS = 'afterSaveGroupPermissions';

    /**
     * Returns all of the known permissions, divided into groups.
     *
     * Each group will have two keys:
     *
     * - `heading` – The human-facing heading text for the group
     * - `permissions` – An array of permissions for the group
     *
     * Each item of the `permissions` array will have a key set to the permission name
     * (e.g. `accessSiteWhenSystemIsOff`), and a value set to an array with the following keys:
     *
     * - `label` – The human-facing permission label
     * - `info` _(optional)_ – Informational text about the permission
     * - `warning` _(optional)_ – Warning text about the permission
     * - `nested` _(optional)_ – An array of nested permissions, which can only be assigned if the parent
     *   permission is assigned.
     */
    public function getAllPermissions(): array
    {
        return $this->service()->getAllPermissions()->toArray();
    }

    /**
     * Returns the permissions that the current user is allowed to assign to another user.
     *
     * See [[getAllPermissions()]] for an explanation of what will be returned.
     *
     * @param  User|null  $user  The recipient of the permissions. If set, their current permissions will be included as well.
     */
    public function getAssignablePermissions(?User $user = null): array
    {
        return $this->service()->getAssignablePermissions($user)->toArray();
    }

    /**
     * Returns all of a given user group's permissions.
     *
     *
     * @return string[]
     */
    public function getPermissionsByGroupId(int $groupId): array
    {
        return $this->service()->getPermissionsByGroupId($groupId)->toArray();
    }

    /**
     * Returns all of the group permissions a given user has.
     *
     *
     * @return string[]
     */
    public function getGroupPermissionsByUserId(int $userId): array
    {
        return $this->service()->getGroupPermissionsByUserId($userId)->toArray();
    }

    /**
     * Returns whether a given user group has a given permission.
     */
    public function doesGroupHavePermission(int $groupId, string $checkPermission): bool
    {
        return $this->service()->doesGroupHavePermission($groupId, $checkPermission);
    }

    /**
     * Saves new permissions for a user group.
     *
     *
     * @throws WrongEditionException if this is called from Craft Solo edition
     */
    public function saveGroupPermissions(int $groupId, array $permissions): bool
    {
        return $this->service()->saveGroupPermissions($groupId, $permissions);
    }

    /**
     * Returns all of a given user’s permissions.
     */
    public function getPermissionsByUserId(int $userId): array
    {
        return $this->service()->getPermissionsByUserId($userId)->toArray();
    }

    /**
     * @since 5.8.13.2
     */
    public function validatePermission(string $permission): bool
    {
        return $this->service()->validatePermission($permission);
    }

    /**
     * Returns whether a given user has a given permission.
     */
    public function doesUserHavePermission(int $userId, string $checkPermission): bool
    {
        return $this->service()->doesUserHavePermission($userId, $checkPermission);
    }

    /**
     * Saves new permissions for a user.
     *
     *
     * @throws WrongEditionException if this is called from Craft Solo edition
     */
    public function saveUserPermissions(int $userId, array $permissions): bool
    {
        return $this->service()->saveUserPermissions($userId, $permissions);
    }

    /**
     * Handle any changed group permissions.
     */
    public function handleChangedGroupPermissions(ConfigEvent $event): void
    {
        $this->service()->handleChangedGroupPermissions($event);
    }

    /**
     * Resets the internal state
     *
     * @since 5.8.13
     */
    public function reset(): void
    {
        $this->service()->reset();
    }

    private function service(): UserPermissionsService
    {
        return app(UserPermissionsService::class);
    }

    /** @internal */
    public static function finalizeRegistrationEvents(): void
    {
        if (!Craft::$app->getUserPermissions()->hasEventHandlers(self::EVENT_REGISTER_PERMISSIONS)) {
            return;
        }

        app(UserPermissionsService::class)->reset();
    }

    public static function registerEvents(): void
    {
        Event::listen(UserGroupPermissionsSaved::class, function(UserGroupPermissionsSaved $event) {
            if (Craft::$app->getUserPermissions()->hasEventHandlers(self::EVENT_AFTER_SAVE_GROUP_PERMISSIONS)) {
                Craft::$app->getUserPermissions()->trigger(self::EVENT_AFTER_SAVE_GROUP_PERMISSIONS, new UserGroupPermissionsEvent([
                    'groupId' => $event->userGroupId,
                    'permissions' => $event->permissions,
                ]));
            }
        });

        Event::listen(UserPermissionsSaved::class, function(UserPermissionsSaved $event) {
            if (Craft::$app->getUserPermissions()->hasEventHandlers(self::EVENT_AFTER_SAVE_USER_PERMISSIONS)) {
                Craft::$app->getUserPermissions()->trigger(self::EVENT_AFTER_SAVE_USER_PERMISSIONS, new UserPermissionsEvent([
                    'userId' => $event->userId,
                    'permissions' => $event->permissions,
                ]));
            }
        });
    }
}
