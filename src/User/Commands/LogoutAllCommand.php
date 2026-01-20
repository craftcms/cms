<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Illuminate\Session\SessionManager;

final class LogoutAllCommand extends Command
{
    use CraftCommand;

    protected $signature = 'craft:users:logout-all';

    protected $description = 'Logs all users out of the system.';

    protected $aliases = ['users/logout-all', 'users/logoutAll', 'users:logoutAll'];

    public function handle(SessionManager $sessionManager): void
    {
        $this->components->task(
            'Logging out all users',
            fn () => $sessionManager->getHandler()->gc(0),
        );
    }
}
