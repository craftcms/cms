<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Users;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Override;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\suggest;

class DeleteCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;
    use PromptsForMissingUser;

    #[Override]
    protected $signature = 'craft:users:delete
        {user}
        {--inheritor= : The email, username or ID of the user to inherit content when deleting a user.}
        {--delete-content : Whether to delete the user’s content if no inheritor is specified.}
        {--hard : Whether the user should be hard-deleted immediately, instead of soft-deleted.}
    ';

    #[Override]
    protected $description = 'Deletes a user.';

    #[Override]
    protected $aliases = ['users/delete'];

    public function handle(Elements $elements, Users $users): int
    {
        if (! $user = $this->getUser()) {
            return self::FAILURE;
        }

        if ($this->option('delete-content') && $this->option('inheritor')) {
            $this->components->error('Only one of --delete-content or --inheritor may be specified.');

            return self::FAILURE;
        }

        if (! ($inheritor = $this->option('inheritor')) && $this->input->isInteractive() && confirm('Transfer this user’s content to an existing user?')) {
            $inheritor = suggest(
                label: 'Which user should inherit the content?',
                options: User::whereNot('id', $user->id)->pluck('username', 'id'),
                required: true,
            );
        }

        if ($inheritor) {
            $inheritor = is_numeric($inheritor)
                ? $users->getUserById((int) $inheritor)
                : $users->getUserByUsernameOrEmail($inheritor);

            if (! $inheritor) {
                $this->components->error("No user exists with a username/email/id of “{$inheritor}”");

                return self::FAILURE;
            }

            if (! confirm("Delete user “{$user->username}” and transfer their content to user “{$inheritor->username}”?")) {
                $this->components->warn('Aborted');

                return self::SUCCESS;
            }

            $user->inheritorOnDelete = $inheritor;
        } elseif (! confirm("Delete user “{$user->username}” and their content?")) {
            $this->components->warn('Aborted');

            return self::SUCCESS;
        }

        if (! $user->inheritorOnDelete && ! $this->option('delete-content')) {
            $this->components->error('You must specify either --delete-content or --inheritor to proceed.');

            return self::FAILURE;
        }

        $fail = false;
        $this->components->task(
            'Deleting the user',
            function () use ($elements, $user, &$fail) {
                $fail = ! $elements->deleteElement($user, $this->option('hard'));
            }
        );

        if ($fail) {
            $this->components->error('Couldn’t delete the user.');

            return self::FAILURE;
        }

        $this->components->success('User deleted.');

        return self::SUCCESS;
    }
}
