<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Commands\Concerns;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
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

        $schema = Schema::connection($connection->getName());

        foreach ($this->tableNames($connection) as $tableName) {
            $this->components->task(
                "Dropping $tableName",
                function () use ($connection, $schema, $tableName) {
                    $this->dropAllForeignKeysToTable($tableName, $connection);
                    $schema->drop($tableName);
                }
            );
        }

        $this->components->success('Finished dropping all database tables.');
    }

    private function dropAllForeignKeysToTable(string $table, Connection $connection): void
    {
        $schema = Schema::connection($connection->getName());
        $rawTableName = $this->rawTableName($table);

        foreach ($this->tableNames($connection) as $otherTableName) {
            foreach ($schema->getForeignKeys($otherTableName) as $foreignKey) {
                if (! isset($foreignKey['foreign_table'], $foreignKey['name'])) {
                    continue;
                }
                if ($this->rawTableName((string) $foreignKey['foreign_table']) !== $rawTableName) {
                    continue;
                }
                $schema->table($otherTableName, function (Blueprint $blueprint) use ($foreignKey) {
                    $blueprint->dropForeign((string) $foreignKey['name']);
                });
            }
        }
    }

    private function rawTableName(string $tableName): string
    {
        return str($tableName)
            ->replace('"', '')
            ->afterLast('.')
            ->value();
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
