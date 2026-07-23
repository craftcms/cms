<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\User\Users;
use CraftCms\Cms\User\Validation\UserRules;
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

    public function handle(Elements $elements, Users $users): int
    {
        if (! $user = $this->getUser()) {
            return self::FAILURE;
        }

        $user->ruleset->useScenario(UserRules::SCENARIO_PASSWORD);
        $user->newPassword = $this->argument('password');

        $saved = false;

        $this->components->task(
            'Saving the user',
            function () use ($elements, $user, &$saved) {
                return $saved = $elements->saveElement($user);
            },
        );

        if (! $saved) {
            foreach ($user->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $users->invalidateUserSessions($user);

        return self::SUCCESS;
    }
}
