<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth;

use Craft;
use CraftCms\Cms\Auth\OAuth\Events\PopulatingUser;
use CraftCms\Cms\Auth\OAuth\Events\PopulatingUserGroups;
use CraftCms\Cms\Auth\OAuth\Exceptions\OAuthException;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;

#[Singleton]
final readonly class OAuthUserResolver
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private OAuthRepository $identities,
        private Users $users,
    ) {}

    public function resolve(Provider $provider): User
    {
        return DB::transaction(function () use ($provider): User {
            $profile = $provider->getProfile();
            $identityId = $this->resolveIdentityId($provider, $profile);

            $user = $this->identities->findUser($provider->handle, $identityId)
                ?? $this->findUser($provider, $profile)
                ?? new User;

            $user = $this->populateUser($provider, $user, $profile);

            if ($user->getStatus() !== User::STATUS_ACTIVE && $provider->activatesUsers) {
                $this->enableUser($user);
            }

            $this->prepareUserForSave($user, $profile, $identityId);

            if (! Craft::$app->getElements()->saveElement($user)) {
                throw new OAuthException(
                    sprintf('Failed to save user: %s', Str::toString($user->getFirstErrors(), ', ')),
                    user: $user,
                );
            }

            if (! $this->identities->link($user, $provider->handle, $identityId)) {
                throw new OAuthException('Failed to link the OAuth identity.', user: $user);
            }

            $this->assignUserToGroups($provider, $user, $profile);

            return $user;
        });
    }

    private function resolveIdentityId(Provider $provider, ProviderProfile $profile): string|int
    {
        $default = $profile->defaultIdentifier();

        return value($provider->uniqueIdCallback, $profile) ?? $default;
    }

    private function findUser(Provider $provider, ProviderProfile $profile): ?User
    {
        if ($user = value($provider->findUserCallback, $profile)) {
            return $user instanceof User ? $user : null;
        }

        if ($profile->email) {
            return $this->users->getUserByUsernameOrEmail($profile->email);
        }

        return null;
    }

    private function populateUser(Provider $provider, User $user, ProviderProfile $profile): User
    {
        if (! is_null($callback = $provider->populateUserCallback)) {
            $result = $callback($user, $profile);

            if ($result instanceof User) {
                $user = $result;
            }
        }

        event($event = new PopulatingUser($user, $profile, $provider));

        return $event->user;
    }

    private function enableUser(User $user): void
    {
        if ($user->getId()) {
            $this->users->activateUser($user);

            return;
        }

        $user->enabled = true;
        $user->archived = false;
        $user->active = true;
        $user->pending = false;
        $user->locked = false;
        $user->suspended = false;
        $user->invalidLoginCount = null;
        $user->lastInvalidLoginDate = null;
        $user->lockoutDate = null;
    }

    private function prepareUserForSave(User $user, ProviderProfile $profile, string|int $identityId): void
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

    private function assignUserToGroups(Provider $provider, User $user, ProviderProfile $profile): void
    {
        if (! $user->id) {
            return;
        }

        $groups = DB::table(Table::USERGROUPS_USERS)
            ->where('userId', $user->id)
            ->pluck('groupId')
            ->all();

        if (! is_null($provider->assignUserGroups)) {
            $groups = value($provider->assignUserGroups, $groups, $profile);
        }

        $groupIds = collect($groups)
            ->map(fn (mixed $group) => UserGroups::getGroup($group)?->id)
            ->filter()
            ->all();

        event($event = new PopulatingUserGroups($user, $groupIds, $profile, $provider));

        if (! $this->users->assignUserToGroups($user->id, $event->groupIds)) {
            throw new OAuthException('Failed to assign OAuth user groups.', user: $user);
        }
    }
}
