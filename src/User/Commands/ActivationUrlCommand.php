<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\User\Users;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\info;

final class ActivationUrlCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;
    use PromptsForMissingUser;

    #[\Override]
    protected $signature = 'craft:users:activation-url {user}';

    #[\Override]
    protected $description = 'Creates a new user.';

    #[\Override]
    protected $aliases = ['users/activation-url', 'users:activationUrl', 'users/activationUrl'];

    public function handle(Users $users): int
    {
        if (! $user = $this->getUser()) {
            return self::FAILURE;
        }

        if (! $user->pending) {
            $this->components->error("User “{$user->username}” has already been activated.");

            return self::FAILURE;
        }

        info("Activation URL for “{$user->username}”:".$users->getActivationUrl($user));

        return self::SUCCESS;
    }
}
