<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\User\Users;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\info;

final class PasswordResetUrlCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;
    use PromptsForMissingUser;

    #[\Override]
    protected $signature = 'craft:users:password-reset-url {user}';

    #[\Override]
    protected $description = 'Generates a password reset URL for a user.';

    #[\Override]
    protected $aliases = ['users/password-reset-url', 'users:passwordResetUrl', 'users/passwordResetUrl'];

    public function handle(Users $users): int
    {
        if (! $user = $this->getUser()) {
            return self::FAILURE;
        }

        info("Password reset URL for “{$user->username}”:".$users->getPasswordResetUrl($user));

        return self::SUCCESS;
    }
}
