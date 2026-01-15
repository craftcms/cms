<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\Methods\AuthMethodInterface;
use CraftCms\Cms\Auth\Methods\RecoveryCodes;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\multiselect;

final class Remove2faCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;
    use PromptsForMissingUser;

    protected $signature = 'craft:users:remove-2fa {user}';

    protected $description = 'Removes user\'s two-step verification method(s)';

    protected $aliases = ['users/remove-2fa'];

    public function handle(Auth $auth): int
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

        $methodsToRemove = multiselect(
            label: "Which two-step verification method(s) would you like to remove for user “{$user->username}”?",
            options: array_keys($activeMethods),
            required: true,
        );

        foreach ($methodsToRemove as $method) {
            $this->remove2faMethod($auth, $activeMethods[$method], $user);
        }

        return self::SUCCESS;
    }

    private function remove2faMethod(Auth $auth, AuthMethodInterface $method, User $user): void
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
