<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Commands\Concerns\ManagesDatabaseTables;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Override;

use function Laravel\Prompts\confirm;

class DropAllTablesCommand extends Command
{
    use CraftCommand;
    use ManagesDatabaseTables;

    #[Override]
    protected $signature = 'craft:db:drop-all-tables';

    #[Override]
    protected $description = 'Drops all tables in the database.';

    #[Override]
    protected $aliases = ['db/drop-all-tables'];

    public function handle(Connection $connection): int
    {
        if (! $this->tablesExist($connection)) {
            $this->components->warn('No existing database tables found.');

            return self::SUCCESS;
        }

        if ($this->input->isInteractive() && ! confirm('Are you sure you want to drop all tables from the database?')) {
            $this->components->warn('Aborted.');

            return self::SUCCESS;
        }

        $this->maybeBackupDatabase();

        $this->dropAllTables($connection);

        return self::SUCCESS;
    }
}
