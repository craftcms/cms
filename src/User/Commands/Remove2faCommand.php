<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Methods\AuthMethodInterface;
use CraftCms\Cms\Auth\Methods\RecoveryCodes;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Override;

use function Laravel\Prompts\multiselect;

class Remove2faCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;
    use PromptsForMissingUser;

    #[Override]
    protected $signature = 'craft:users:remove-2fa {user} {--method= : The two-step verification method to remove.}';

    #[Override]
    protected $description = 'Removes user\'s two-step verification method(s)';

    #[Override]
    protected $aliases = ['users/remove-2fa'];

    public function handle(AuthMethods $auth): int
    {
        if (! $user = $this->getUser()) {
            return self::FAILURE;
        }

        $activeMethods = $auth->getActiveMethods($user);

        if ($activeMethods->isEmpty()) {
            $this->components->info("User “{$user->username}” doesn’t have any active two-step verification methods.");

            return self::SUCCESS;
        }

        $activeMethods = array_combine(
            $activeMethods->map(fn (AuthMethodInterface $method) => $method::displayName())->all(),
            $activeMethods->all(),
        );

        if (($method = $this->option('method')) && $method !== 'all' && ! isset($activeMethods[$this->option('method')])) {
            $this->components->info("User “{$user->username}” doesn’t have the “{$method}” two-step verification method.");

            return self::SUCCESS;
        }

        if ($method === 'all') {
            $methodsToRemove = array_keys($activeMethods);
        } elseif ($method) {
            $methodsToRemove = [$method];
        } else {
            $methodsToRemove = multiselect(
                label: "Which two-step verification method(s) would you like to remove for user “{$user->username}”?",
                options: array_keys($activeMethods),
                required: true,
            );
        }

        foreach ($methodsToRemove as $method) {
            $this->remove2faMethod($auth, $activeMethods[$method], $user);
        }

        return self::SUCCESS;
    }

    private function remove2faMethod(AuthMethods $auth, AuthMethodInterface $method, User $user): void
    {
        $this->components->task(
            "Removing “{$method::displayName()}” two-step verification method for the user ...",
            function () use ($method) {
                $method->remove();
            }
        );

        if ($auth->getActiveMethods($user)->isNotEmpty()) {
            return;
        }

        // if that was the last non-Recovery Codes method, remove Recovery Codes too
        $recoveryCodes = $auth->getMethod(RecoveryCodes::class, $user);

        if (! $recoveryCodes->isActive()) {
            return;
        }

        $this->components->task(
            "No further two-step verification methods left. Removing “{$recoveryCodes::displayName()}” for the user ...",
            fn () => $recoveryCodes->remove(),
        );
    }
}
