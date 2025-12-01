<?php

declare(strict_types=1);

namespace CraftCms\Cms\User;

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
use Illuminate\Support\ServiceProvider;

final class UserServiceProvider extends ServiceProvider
{
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
