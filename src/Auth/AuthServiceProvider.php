<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Override;

final class AuthServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->registerRedirects();
        $this->registerGuard();
        $this->registerPermissions();
        $this->registerEvents();
    }

    private function registerRedirects(): void
    {
        Authenticate::redirectUsing(function (Request $request) {
            if ($request->isCpRequest()) {
                return Cms::config()->cpTrigger.'/login';
            }

            return Cms::config()->loginPath;
        });

        RedirectIfAuthenticated::redirectUsing(fn (Request $request) => URL::defaultReturnUrl());
    }

    private function registerGuard(): void
    {
        Auth::provider('craft', fn (Application $app) => new UserProvider($app->make(Hasher::class)));

        if (! Config::has('auth.guards.craft')) {
            Config::set('auth.guards.craft', [
                'driver' => 'session',
                'provider' => 'craft',
                'remember' => floor(Cms::config()->rememberedUserSessionDuration / 60),
            ]);
        }

        if (! Config::has('auth.providers.craft')) {
            Config::set('auth.providers.craft', [
                'driver' => 'craft',
                'model' => User::class,
            ]);
        }
    }

    private function registerPermissions(): void
    {
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

    private function registerEvents(): void
    {
        Event::listen(Login::class, function (Login $event) {
            if (! $event->user instanceof User) {
                return;
            }

            Users::handleValidLogin($event->user);

            RememberedUsername::set($event->user);

            Session::passwordConfirmed();
        });

        Event::listen(Failed::class, function (Failed $event) {
            if (! $event->user instanceof User) {
                return;
            }

            Users::handleInvalidLogin($event->user);
        });

        Event::listen(Logout::class, function () {
            app(Impersonation::class)->setImpersonatorId(null);
        });
    }
}
