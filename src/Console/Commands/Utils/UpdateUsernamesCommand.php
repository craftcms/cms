<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Utils;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Table;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class UpdateUsernamesCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:utils:update-usernames';

    #[\Override]
    protected $description = 'Updates all users’ usernames to ensure they match their email address.';

    #[\Override]
    protected $aliases = ['utils/update-usernames'];

    public function handle(GeneralConfig $generalConfig): int
    {
        if (! $generalConfig->useEmailAsUsername) {
            $this->components->error('The `useEmailAsUsername` config setting is not enabled.');

            return self::FAILURE;
        }

        $affected = DB::table(Table::USERS)
            ->whereColumn('username', '!=', 'email')
            ->update([
                'username' => DB::raw('email'),
            ]);

        $this->components->success("$affected usernames updated.");

        return self::SUCCESS;
    }
}
