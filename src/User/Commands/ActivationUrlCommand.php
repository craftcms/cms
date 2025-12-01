<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use Craft;
use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\info;

final class ActivationUrlCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;
    use PromptsForMissingUser;

    protected $signature = 'craft:users:activation-url {user}';

    protected $description = 'Creates a new user.';

    protected $aliases = ['users/activation-url', 'users:activationUrl', 'users/activationUrl'];

    public function handle(): int
    {
        if (! $user = $this->getUser()) {
            return self::FAILURE;
        }

        if (! $user->pending) {
            $this->components->error("User “{$user->username}” has already been activated.");

            return self::FAILURE;
        }

        info("Activation URL for “{$user->username}”:".Craft::$app->getUsers()->getActivationUrl($user));

        return self::SUCCESS;
    }
}
