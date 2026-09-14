<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Policies\AddressPolicy;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Policies\AssetPolicy;
use CraftCms\Cms\Asset\Policies\VolumeFolderPolicy;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Policies\EntryPolicy;
use CraftCms\Cms\Field\Elements\ContentBlock;
use CraftCms\Cms\Field\Policies\ContentBlockPolicy;
use CraftCms\Cms\Structure\Data\Structure;
use CraftCms\Cms\Structure\Policies\StructurePolicy;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Users as UsersFacade;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Notifications\SendQueuedUserNotifications;
use CraftCms\Cms\User\Policies\UserPolicy;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Override;

class AuthServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        if (! class_exists($this->app->make(Repository::class)->get('auth.providers.users.model'))) {
            $this->app->make(Repository::class)->set('auth.providers.users.model', User::class);
        }

        $this->app->bind(SendQueuedNotifications::class, SendQueuedUserNotifications::class);

        $this->registerPermissions();
        $this->registerEvents();
    }

    public function boot(): void
    {
        $this->bootRedirects();
        $this->registerElementPolicies();
    }

    private function bootRedirects(): void
    {
        Authenticate::redirectUsing(function (Request $request) {
            if ($request->isCpRequest()) {
                return Cms::config()->cpTrigger.'/login';
            }

            return Cms::config()->getLoginPath();
        });

        RedirectIfAuthenticated::redirectUsing(fn (Request $request) => URL::defaultReturnUrl());
    }

    private function registerPermissions(): void
    {
        /**
         * This hooks our permission system into
         * Laravel's Gate authorization system
         */
        Gate::after(function (CraftUser $user, string $ability, ?bool $result) {
            /**
             * Only check our permissions when the
             * result was not explicitly set.
             */
            if (! is_null($result)) {
                return $result;
            }

            if (
                $user->isAdmin() ||
                Edition::get() === Edition::Solo
            ) {
                return true;
            }

            $userId = $user->getCraftUserId();

            if (! $userId) {
                return null;
            }

            if (! app(UserPermissions::class)->doesUserHavePermission($userId, $ability)) {
                return null;
            }

            return true;
        });
    }

    private function registerEvents(): void
    {
        Event::listen(function (Authenticated $event) {
            Sites::refreshSites();
            app(RequestedSite::class)->reset();
        });

        Event::listen(Login::class, function (Login $event) {
            $user = $event->user instanceof CraftUser ? $event->user->asElement() : null;

            if (! $user) {
                return;
            }

            UsersFacade::handleValidLogin($user);

            app(AuthMethods::class)->setRememberedUsername($user);

            Session::passwordConfirmed();
        });

        Event::listen(Failed::class, function (Failed $event) {
            $user = $event->user instanceof CraftUser ? $event->user->asElement() : null;

            if (! $user) {
                return;
            }

            UsersFacade::handleInvalidLogin($user);
        });

        Event::listen(Logout::class, function () {
            app(Impersonation::class)->setImpersonatorId(null);
        });
    }

    private function registerElementPolicies(): void
    {
        Gate::policy(Element::class, ElementPolicy::class);
        Gate::policy(Address::class, AddressPolicy::class);
        Gate::policy(Asset::class, AssetPolicy::class);
        Gate::policy(VolumeFolder::class, VolumeFolderPolicy::class);
        Gate::policy(ContentBlock::class, ContentBlockPolicy::class);
        Gate::policy(Entry::class, EntryPolicy::class);
        Gate::policy(Structure::class, StructurePolicy::class);
        Gate::policy(UserElement::class, UserPolicy::class);
    }
}
