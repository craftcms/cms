<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use Craft;
use craft\auth\methods\AuthMethodInterface;
use craft\auth\methods\RecoveryCodes;
use craft\elements\User;
use CraftCms\Cms\Console\CraftCommand;
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

    public function handle(): int
    {
        if (! $user = $this->getUser()) {
            return self::FAILURE;
        }

        $authService = Craft::$app->getAuth();
        $activeMethods = $authService->getActiveMethods($user);

        if (empty($activeMethods)) {
            $this->components->info("User “{$user->username}” doesn’t have any active two-step verification methods.");

            return self::SUCCESS;
        }

        $activeMethods = array_combine(
            array_map(fn (AuthMethodInterface $method) => $method::displayName(), $activeMethods),
            $activeMethods,
        );

        $methodsToRemove = multiselect(
            label: "Which two-step verification method(s) would you like to remove for user “{$user->username}”?",
            options: array_keys($activeMethods),
            required: true,
        );

        foreach ($methodsToRemove as $method) {
            $this->remove2faMethod($activeMethods[$method], $user);
        }

        return self::SUCCESS;
    }

    private function remove2faMethod(AuthMethodInterface $method, User $user): void
    {
        $this->components->task(
            "Removing “{$method::displayName()}” two-step verification method for the user ...",
            function () use ($method) {
                $method->remove();
            }
        );

        $auth = Craft::$app->getAuth();

        // if that was the last non-Recovery Codes method, remove Recovery Codes too
        if (empty($auth->getActiveMethods($user))) {
            $recoveryCodes = $auth->getMethod(RecoveryCodes::class, $user);

            if ($recoveryCodes->isActive()) {
                $this->components->task(
                    "No further two-step verification methods left. Removing “{$recoveryCodes::displayName()}” for the user ...",
                    fn () => $recoveryCodes->remove(),
                );
            }
        }
    }
}
