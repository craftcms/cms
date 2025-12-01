<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use Craft;
use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\info;

final class PasswordResetUrlCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;
    use PromptsForMissingUser;

    protected $signature = 'craft:users:password-reset-url {user}';

    protected $description = 'Generates a password reset URL for a user.';

    protected $aliases = ['users/password-reset-url', 'users:passwordResetUrl', 'users/passwordResetUrl'];

    public function handle(): int
    {
        if (! $user = $this->getUser()) {
            return self::FAILURE;
        }

        info("Password reset URL for “{$user->username}”:".Craft::$app->getUsers()->getPasswordResetUrl($user));

        return self::SUCCESS;
    }
}
