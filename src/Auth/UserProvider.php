<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\Auth\Events\LoginUserRetrieved;
use CraftCms\Cms\Auth\Events\RetrievingLoginUser;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use SensitiveParameter;

#[Scoped]
final readonly class UserProvider implements \Illuminate\Contracts\Auth\UserProvider
{
    /**
     * Create a new Craft user provider.
     */
    public function __construct(
        private HasherContract $hasher,
        private Users $users,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function retrieveById($identifier): ?User
    {
        return User::find()->addSelect('password')->id($identifier)->one();
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveByToken($identifier, #[SensitiveParameter] $token): ?User
    {
        $user = $this->retrieveById($identifier);

        if (! $user) {
            return null;
        }

        if ($user->getRememberToken() && hash_equals($user->getRememberToken(), $token)) {
            return $user;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function updateRememberToken(Authenticatable $user, #[SensitiveParameter] $token): void
    {
        DB::table(Table::USERS)
            ->where('id', $user->getAuthIdentifier())
            ->update(['rememberToken' => $token]);
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveByCredentials(#[SensitiveParameter] array $credentials): ?User
    {
        $credentials = array_filter(
            $credentials,
            fn ($key) => ! str_contains((string) $key, 'password'),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($credentials)) {
            return null;
        }

        $loginName = $credentials['loginName'];

        $user = null;
        if (Event::hasListeners(RetrievingLoginUser::class)) {
            Event::dispatch($event = new RetrievingLoginUser($loginName));
            $user = $event->user;
        }

        $user ??= $this->users->getUserByUsernameOrEmail($loginName);

        if (Event::hasListeners(LoginUserRetrieved::class)) {
            Event::dispatch($event = new LoginUserRetrieved($loginName, $user));

            return $event->user;
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     *
     * @param  User  $user
     */
    public function validateCredentials(Authenticatable $user, #[SensitiveParameter] array $credentials): bool
    {
        return app(Auth::class)->authenticate($user, $credentials);
    }

    /**
     * {@inheritDoc}
     */
    public function rehashPasswordIfRequired(
        Authenticatable $user,
        #[SensitiveParameter] array $credentials,
        bool $force = false,
    ): void {
        if (! $this->hasher->needsRehash($user->getAuthPassword()) && ! $force) {
            return;
        }

        DB::table(Table::USERS)
            ->where('id', $user->getAuthIdentifier())
            ->update([$user->getAuthPasswordName() => $this->hasher->make($credentials['password'])]);
    }

    public function getAuthError(): ?AuthError
    {
        return app(Auth::class)->authError;
    }
}
