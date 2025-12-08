<?php

declare(strict_types=1);

namespace CraftCms\Cms\User;

use CraftCms\Cms\Edition;
use CraftCms\Cms\User\Commands\ActivationUrlCommand;
use CraftCms\Cms\User\Commands\CreateCommand;
use CraftCms\Cms\User\Commands\DeleteCommand;
use CraftCms\Cms\User\Commands\ImpersonateCommand;
use CraftCms\Cms\User\Commands\ListAdminsCommand;
use CraftCms\Cms\User\Commands\LogoutAllCommand;
use CraftCms\Cms\User\Commands\PasswordResetUrlCommand;
use CraftCms\Cms\User\Commands\Remove2faCommand;
use CraftCms\Cms\User\Commands\SetPasswordCommand;
use CraftCms\Cms\User\Commands\UnlockCommand;
use CraftCms\Cms\User\Models\User;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Override;

final class UserServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        /**
         * This hooks our permission system into
         * Laravel's Gate authorization system
         */
        Gate::before(function (Authorizable $user, string $ability) {
            if (! $user instanceof User) {
                return null;
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

    public function boot(): void
    {
        $this->commands([
            ActivationUrlCommand::class,
            CreateCommand::class,
            DeleteCommand::class,
            ImpersonateCommand::class,
            ListAdminsCommand::class,
            LogoutAllCommand::class,
            PasswordResetUrlCommand::class,
            Remove2faCommand::class,
            SetPasswordCommand::class,
            UnlockCommand::class,
        ]);
    }
}
