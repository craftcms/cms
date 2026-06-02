<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\User\Users;
use Illuminate\Console\Attributes\Aliases;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\info;

#[Aliases(['users/activation-url', 'users:activationUrl', 'users/activationUrl'])]
#[Description('Creates a new user.')]
#[Signature('craft:users:activation-url {user}')]
class ActivationUrlCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;
    use PromptsForMissingUser;

    public function handle(Users $users): int
    {
        if (! $user = $this->getUser()) {
            return self::FAILURE;
        }

        if (! $user->pending) {
            $this->components->error("User “{$user->username}” has already been activated.");

            return self::FAILURE;
        }

        info("Activation URL for “{$user->username}”: ".$users->getActivationUrl($user));

        return self::SUCCESS;
    }
}
