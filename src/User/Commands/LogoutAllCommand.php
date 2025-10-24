<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Table;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class LogoutAllCommand extends Command
{
    use CraftCommand;

    protected $signature = 'craft:users:logout-all';

    protected $description = 'Logs all users out of the system.';

    protected $aliases = ['users/logout-all', 'users/logoutAll', 'users:logoutAll'];

    public function handle(): void
    {
        $this->components->task(
            'Logging out all users',
            fn () => DB::table(Table::SESSIONS)->truncate(),
        );
    }
}
