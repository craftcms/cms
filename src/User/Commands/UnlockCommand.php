<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use Craft;
use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

final class UnlockCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;
    use PromptsForMissingUser;

    protected $signature = 'craft:users:unlock {user}';

    protected $description = 'Unlocks a user\'s account.';

    protected $aliases = ['users/unlock'];

    public function handle(): int
    {
        if (! $user = $this->getUser()) {
            return self::FAILURE;
        }

        if (! $user->locked) {
            $this->info("User “{$user->username}” is not locked.");

            return self::SUCCESS;
        }

        $this->components->task(
            'Unlocking the user',
            fn () => Craft::$app->getUsers()->unlockUser($user),
        );

        return self::SUCCESS;
    }
}
