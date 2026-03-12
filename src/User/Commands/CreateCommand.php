<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use Craft;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Console\Command;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:users:create
        {--email= : The user’s email address.}
        {--username= : The user’s username.}
        {--password= : The user’s new password.}
        {--admin= : Whether the user should be an admin.}
        {--activate= : Whether the user account should be activated.}
        {--send-activation-email : Whether to send the user an activation email.}
        {--groups=* : The group handles to assign the created user to.}
        {--groupIds=* : The group IDs to assign the created user to.}
    ';

    #[\Override]
    protected $description = 'Creates a new user.';

    #[\Override]
    protected $aliases = ['users/create'];

    public function handle(GeneralConfig $generalConfig, Users $users): int
    {
        if (! $users->canCreateUsers()) {
            $this->components->error('The maximum number of users has already been reached.');

            return self::FAILURE;
        }

        $attributes = Arr::whereNotNull([
            'email' => $this->option('email'),
            'username' => $this->option('username'),
            'newPassword' => $this->option('password'),
            'admin' => ! is_null($this->option('admin'))
                ? filter_var($this->option('admin'), FILTER_VALIDATE_BOOLEAN)
                : null,
        ]);

        $user = new User($attributes);

        if (! empty($attributes) && ! $user->validate(array_keys($attributes))) {
            $this->error('Invalid arguments:');
            $this->error(implode(PHP_EOL, $user->getErrorSummary(true)));

            return self::FAILURE;
        }

        if ($generalConfig->useEmailAsUsername) {
            $user->username = $this->option('email') ?? text(
                label: 'Email:',
                required: true,
                validate: ['email:strict', Rule::unique(Table::USERS, 'email')],
            );
        } else {
            $user->email = $this->option('email') ?? text(
                label: 'Email:',
                required: true,
                validate: ['email:strict', Rule::unique(Table::USERS, 'email')],
            );

            $user->username = $this->option('username') ?? text(
                label: 'Username:',
                required: true,
                validate: ['alpha_num', Rule::unique(Table::USERS, 'username')],
            );
        }

        $user->admin = ! is_null($this->option('admin'))
            ? filter_var($this->option('admin'), FILTER_VALIDATE_BOOLEAN)
            : ($this->input->isInteractive() && confirm('Make this user an admin?'));

        if ($this->option('password')) {
            $user->newPassword = $this->option('password');
        } elseif ($this->input->isInteractive()) {
            if (confirm('Set a password for this user?')) {
                $user->newPassword = password(
                    label: 'Password:',
                    required: true,
                    validate: [Password::required()],
                );
            }
        }

        if (! is_null($this->option('activate'))) {
            $user->active = filter_var($this->option('activate'), FILTER_VALIDATE_BOOLEAN);
        } else {
            $defaultActivate = $user->newPassword !== null;
            $user->active = $this->input->isInteractive()
                ? confirm('Activate the account?', $defaultActivate)
                : $defaultActivate;
        }

        $failed = false;
        $this->components->task(
            description: 'Saving the user',
            task: function () use ($user, &$failed) {
                $failed = ! Craft::$app->getElements()->saveElement($user, false);

                return $failed;
            }
        );

        if ($failed) {
            $this->components->error('Failed to save the user.');
            $this->components->error(implode(PHP_EOL, $user->getErrorSummary(true)));

            return self::FAILURE;
        }

        $groupIds = array_merge(
            $this->option('groupIds'),
            array_map(
                fn ($handle) => UserGroups::getGroupByHandle($handle)->id ?? null,
                $this->option('groups')
            ),
        );

        if ($groupIds) {
            $this->components->task(
                description: 'Assigning the user to groups',
                task: function () use ($users, $user, $groupIds) {
                    $users->assignUserToGroups($user->id, $groupIds);
                }
            );
        }

        if (! $user->active) {
            if ($this->option('send-activation-email') || confirm('Send an activation email now?')) {
                $this->components->task(
                    'Sending activation email...',
                    function () use ($user) {
                        $user->sendEmailVerificationNotification();
                    }
                );

                return self::SUCCESS;
            }

            $this->call('craft:users:activation-url', ['user' => $user->id]);
        }

        return self::SUCCESS;
    }
}
