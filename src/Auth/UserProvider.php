<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use CraftCms\Cms\Auth\Events\LoginUserRetrieved;
use CraftCms\Cms\Auth\Events\RetrievingLoginUser;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use SensitiveParameter;

final readonly class UserProvider implements \Illuminate\Contracts\Auth\UserProvider
{
    /**
     * Create a new Craft user provider.
     */
    public function __construct(
        private HasherContract $hasher
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

        $user ??= Users::getUserByUsernameOrEmail($loginName);

        if (Event::hasListeners(LoginUserRetrieved::class)) {
            Event::dispatch($event = new LoginUserRetrieved($loginName, $user));

            return $event->user;
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function validateCredentials(Authenticatable $user, #[SensitiveParameter] array $credentials): bool
    {
        if (is_null($plain = $credentials['password'])) {
            return false;
        }

        if (is_null($hashed = $user->getAuthPassword())) {
            return false;
        }

        return $this->hasher->check($plain, $hashed);
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
}
