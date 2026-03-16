<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Commands\Concerns\ManagesDatabaseTables;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Override;

use function Laravel\Prompts\confirm;

class RepairCommand extends Command
{
    use CraftCommand;
    use ManagesDatabaseTables;

    #[Override]
    protected $signature = 'craft:db:repair';

    #[Override]
    protected $description = 'Repairs all tables in the database.';

    #[Override]
    protected $aliases = ['db/repair'];

    public function handle(Connection $connection): int
    {
        if (! $this->tablesExist($connection)) {
            $this->components->warn('No existing database tables found.');

            return self::SUCCESS;
        }

        if ($this->input->isInteractive() && ! confirm('Are you sure you want to repair all tables from the database?')) {
            $this->components->warn('Aborted.');

            return self::SUCCESS;
        }

        $this->maybeBackupDatabase();
        $this->repairAllTables($connection);

        return self::SUCCESS;
    }

    private function repairAllTables(Connection $connection): void
    {
        $this->components->info('Repairing all database tables ...');

        $sql = $connection->isMysql()
            ? 'OPTIMIZE TABLE %s'
            : 'ANALYZE VERBOSE %s';

        foreach ($this->tableNames($connection) as $tableName) {
            $this->components->task(
                "Repairing `$tableName`",
                function () use ($connection, $sql, $tableName) {
                    $wrappedTable = $connection->getSchemaGrammar()->wrapTable($tableName);
                    $connection->statement(sprintf($sql, $wrappedTable));
                }
            );
        }

        $this->components->success('Finished repairing all database tables.');
    }
}
