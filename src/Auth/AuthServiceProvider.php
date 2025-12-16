<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use CraftCms\Cms\Edition;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Override;

final class AuthServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        Auth::provider('craft', fn (Application $app) => new UserProvider($app->make(Hasher::class)));

        if (! Config::has('auth.guards.craft')) {
            Config::set('auth.guards.craft', [
                'driver' => 'session',
                'provider' => 'craft',
            ]);
        }

        if (! Config::has('auth.providers.craft')) {
            Config::set('auth.providers.craft', [
                'driver' => 'craft',
                'model' => User::class,
            ]);
        }

        /**
         * This hooks our permission system into
         * Laravel's Gate authorization system
         */
        Gate::after(function (Authorizable $user, string $ability, ?bool $result) {
            if (! $user instanceof User) {
                return null;
            }

            /**
             * Only check our permissions when the
             * result was not explicitly set.
             */
            if (! is_null($result)) {
                return $result;
            }

            if (
                $user->admin ||
                Edition::get() === Edition::Solo
            ) {
                return true;
            }

            if (! isset($user->id)) {
                return null;
            }

            if (! app(UserPermissions::class)->doesUserHavePermission($user->id, $ability)) {
                return null;
            }

            return true;
        });
    }
}
