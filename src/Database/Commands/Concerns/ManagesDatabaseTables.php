<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Commands\Concerns;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\confirm;

/**
 * @mixin Command
 */
trait ManagesDatabaseTables
{
    protected function tablesExist(Connection $connection): bool
    {
        return ! empty($this->tableNames($connection));
    }

    protected function maybeBackupDatabase(): void
    {
        if (! $this->input->isInteractive() || ! confirm('Backup your database?')) {
            return;
        }

        $this->call('craft:db:backup');
    }

    protected function dropAllTables(Connection $connection): void
    {
        $this->components->info('Dropping all database tables ...');

        $connection->getSchemaBuilder()->dropAllTables();

        $this->components->success('Finished dropping all database tables.');
    }

    /**
     * @return list<string>
     */
    private function tableNames(Connection $connection): array
    {
        $schema = Schema::connection($connection->getName());
        $currentSchemas = $schema->getCurrentSchemaListing();

        if (empty($currentSchemas)) {
            $currentSchemas = $schema->getCurrentSchemaName();
        }

        return collect($schema->getTables($currentSchemas))
            ->pluck('name')
            ->filter(fn (mixed $name): bool => is_string($name) && $name !== '')
            ->values()
            ->all();
    }
}
