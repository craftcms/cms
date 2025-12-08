<?php

declare(strict_types=1);

namespace CraftCms\Cms\User;

use Closure;
use Craft;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\DB;
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
        $user = User::find()
            ->addSelect(['users.password'])
            ->id($identifier)
            ->status(null)
            ->first();

        if ($user === null) {
            return null;
        }

        // Only accept active users, unless they're being impersonated
        if (
            $user->getStatus() !== User::STATUS_ACTIVE &&
            ! Craft::$app->getUser()->getImpersonator()
        ) {
            return null;
        }

        return $user;
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
            ->update([$user->getRememberTokenName() => $token]);
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

        $query = User::find();

        foreach ($credentials as $key => $value) {
            if (is_array($value) || $value instanceof Arrayable) {
                $query->whereIn($key, $value);
            } elseif ($value instanceof Closure) {
                $value($query);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->first();
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
