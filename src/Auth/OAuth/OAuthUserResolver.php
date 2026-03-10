<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth;

use Craft;
use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\OAuth\Events\PopulatingUser;
use CraftCms\Cms\Auth\OAuth\Events\PopulatingUserGroups;
use CraftCms\Cms\Auth\OAuth\Exceptions\OAuthException;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Singleton]
final readonly class OAuthUserResolver
{
    public function __construct(
        private Auth $auth,
        private GeneralConfig $generalConfig,
        private OAuthRepository $identities,
        private Users $users,
    ) {}

    public function resolveAndLogin(ProviderDefinition $provider, Profile $profile, bool $remember = false): User
    {
        $user = $this->resolve($provider, $profile);

        if ($authError = $this->auth->getAuthError($user)) {
            throw new OAuthException(
                user: $user,
                authError: $authError,
            );
        }

        auth('craft')->login($user, $remember);

        return $user;
    }

    public function resolve(ProviderDefinition $provider, Profile $profile): User
    {
        $identityId = $this->resolveIdentityId($provider, $profile);
        $user = $this->identities->findUser($provider->handle, $identityId)
            ?? $this->findUser($provider, $profile)
            ?? new User;

        $user = $this->populateUser($provider, $user, $profile);

        $this->activateUser($user);
        $this->prepareUserForSave($user, $profile, $identityId);

        if (! Craft::$app->getElements()->saveElement($user)) {
            throw new OAuthException(
                sprintf('Failed to save user: %s', Str::toString($user->getFirstErrors(), ', ')),
                user: $user,
            );
        }

        $this->identities->link($user, $provider->handle, $identityId);
        $this->assignUserToGroups($provider, $user, $profile);

        return $user;
    }

    private function resolveIdentityId(ProviderDefinition $provider, Profile $profile): string|int
    {
        $default = $profile->defaultIdentifier();

        return value($provider->idpUniqueIdentifier, $profile) ?? $default;
    }

    private function findUser(ProviderDefinition $provider, Profile $profile): ?User
    {
        if ($user = value($provider->findUser, $profile)) {
            return $user instanceof User ? $user : null;
        }

        if ($profile->email) {
            return $this->users->getUserByUsernameOrEmail($profile->email);
        }

        return null;
    }

    private function populateUser(ProviderDefinition $provider, User $user, Profile $profile): User
    {
        if (($result = value($provider->populateUser, $user, $profile)) && $result instanceof User) {
            $user = $result;
        }

        event($event = new PopulatingUser($user, $profile, $provider));

        return $event->user;
    }

    private function activateUser(User $user): void
    {
        if (! $user->id) {
            $user->enabled = true;
            $user->archived = false;
            $user->active = true;
            $user->pending = false;
            $user->locked = false;
            $user->suspended = false;
            $user->invalidLoginCount = null;
            $user->lastInvalidLoginDate = null;
            $user->lockoutDate = null;

            return;
        }

        if ($user->getStatus() !== User::STATUS_ACTIVE) {
            $this->users->activateUser($user);
        }
    }

    private function prepareUserForSave(User $user, Profile $profile, string|int $identityId): void
    {
        $user->email ??= $profile->email;

        if ($user->id) {
            return;
        }

        if (! $user->username || $this->generalConfig->useEmailAsUsername) {
            $user->username = $profile->email
                ?? $profile->nickname
                ?? ($profile->name ? Str::slug($profile->name) : null)
                ?? Str::slug(sprintf('%s-%s', $profile->handle, $identityId));
        }
    }

    private function assignUserToGroups(ProviderDefinition $provider, User $user, Profile $profile): void
    {
        if (! $user->id) {
            return;
        }

        $groups = DB::table(Table::USERGROUPS_USERS)
            ->where('userId', $user->id)
            ->pluck('groupId')
            ->all();

        if (! is_null($provider->assignUserGroups)) {
            $groups = value($provider->assignUserGroups, $profile, $groups);
        }

        $groupIds = array_map(function (mixed $group) {
            if (is_int($group)) {
                return $group;
            }

            if (is_string($group)) {
                if (Str::isUuid($group)) {
                    return UserGroups::getGroupByUid($group)?->id;
                }

                return UserGroups::getGroupByHandle($group)?->id;
            }

            return null;
        }, Arr::wrap($groups));

        $groupIds = array_filter($groupIds);

        event($event = new PopulatingUserGroups($user, $groupIds, $profile, $provider));

        $this->users->assignUserToGroups($user->id, $event->groupIds);
    }
}
