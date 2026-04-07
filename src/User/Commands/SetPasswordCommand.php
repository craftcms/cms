<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Override;

class SetPasswordCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;
    use PromptsForMissingUser;

    #[Override]
    protected $signature = 'craft:users:set-password {user} {password}';

    #[Override]
    protected $description = 'Changes a user’s password.';

    #[Override]
    protected $aliases = ['users/set-password', 'users/setPassword', 'users:setPassword'];

    public function handle(Elements $elements): int
    {
        if (! $user = $this->getUser()) {
            return self::FAILURE;
        }

        $user->setScenario(User::SCENARIO_PASSWORD);
        $user->newPassword = $this->argument('password');

        $this->components->task(
            'Saving the user',
            fn () => $elements->saveElement($user, false),
        );

        return self::SUCCESS;
    }
}
