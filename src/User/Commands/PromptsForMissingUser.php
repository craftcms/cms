<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\User\Models\User;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\search;

trait PromptsForMissingUser
{
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'user' => fn () => search(
                label: 'Search for a user:',
                options: fn ($value) => strlen($value) > 0
                    ? User::query()
                        ->where(function ($query) use ($value) {
                            $query->whereLike('username', "%{$value}%")
                                ->orWhereLike('email', "%{$value}%");
                        })
                        ->join(Table::ELEMENTS, Table::ELEMENTS.'.id', '=', Table::USERS.'.id')
                        ->whereNull(Table::ELEMENTS.'.dateDeleted')
                        ->select(['username', 'email', Table::USERS.'.id'])
                        ->get()
                        ->mapWithKeys(fn (User $user) => [$user->id => "{$user->username} ".(Cms::config()->useEmailAsUsername ? '' : "({$user->email})")])
                        ->all()
                    : []
            ),
            'password' => fn () => password('Password:', validate: [Password::required()]),
        ];
    }

    protected function getUser(): ?\craft\elements\User
    {
        $value = $this->argument('user');

        if (is_numeric($value)) {
            $user = Craft::$app->getUsers()->getUserById((int) $value);
        } else {
            $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($value);
        }

        if (! $user) {
            $this->components->error('No user found with '.(is_numeric($this->argument('user')) ? 'ID' : 'username or email').$value);
        }

        return $user;
    }
}
