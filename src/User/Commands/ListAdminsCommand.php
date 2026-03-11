<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Laravel\Prompts\Concerns\Colors;

use function Laravel\Prompts\table;

final class ListAdminsCommand extends Command
{
    use Colors;
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:users:list-admins';

    #[\Override]
    protected $description = 'Lists admin users.';

    #[\Override]
    protected $aliases = ['users/list-admins'];

    public function handle(GeneralConfig $generalConfig): void
    {
        /** @var Collection<User> $users */
        $users = User::find()
            ->admin()
            ->status(null)
            ->orderBy('username')
            ->get();

        $total = count($users);

        $this->components->info($total.' '.Str::plural('admin', $total).' found.');

        table(
            headers: array_filter([
                $generalConfig->useEmailAsUsername ? 'Email' : 'Username',
                $generalConfig->useEmailAsUsername ? null : 'Email',
                'Status',
            ]),
            rows: $users->map(fn (User $user) => array_filter([
                $generalConfig->useEmailAsUsername ? $user->email : $user->username,
                $generalConfig->useEmailAsUsername ? null : $user->email,
                match ($user->getStatus()) {
                    User::STATUS_SUSPENDED => $this->red('suspended'),
                    User::STATUS_ARCHIVED => $this->red('archived'),
                    User::STATUS_PENDING => $this->yellow('pending'),
                    default => $this->green('active'),
                },
            ]))->all(),
        );
    }
}
