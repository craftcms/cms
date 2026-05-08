<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\BackupCommands;

use RuntimeException;

class SqliteBackupCommand extends BackupCommand
{
    public function backup(): string
    {
        $config = $this->getConnectionConfig();
        $schemaBuilder = $this->connection->getSchemaBuilder();

        $this->ensureFileBackedSqliteDatabase($config['database'], 'backed up');
        $dataTables = collect($schemaBuilder->getTables())
            ->pluck('name')
            ->diff(collect($this->ignoreTables)->map(fn (string $table) => $this->tableName($table)))
            ->values()
            ->all();

        $parts = [
            'set -e; {',
            'printf %s\\\\n '.implode(' ', array_map(escapeshellarg(...), [
                'PRAGMA foreign_keys=OFF;',
                'BEGIN TRANSACTION;',
            ])).';',
            $this->resolveExecutable('sqlite3').' '.escapeshellarg((string) $config['database']).' '.escapeshellarg('.schema --nosys').';',
        ];

        if (! empty($dataTables)) {
            $parts[] = $this->resolveExecutable('sqlite3').' '.escapeshellarg((string) $config['database']).' '.escapeshellarg(
                '.dump --data-only --nosys '.implode(' ', array_map($this->sqliteDotCommandArgument(...), $dataTables))
            ).';';
        }

        $parts[] = 'printf %s\\\\n '.implode(' ', array_map(escapeshellarg(...), [
            'COMMIT;',
            'PRAGMA foreign_keys=ON;',
        ])).';';
        $parts[] = '} > '.escapeshellarg($this->filePath);

        return implode(' ', $parts);
    }

    public function restore(): string
    {
        $config = $this->getConnectionConfig();
        $schemaBuilder = $this->connection->getSchemaBuilder();
        $queryGrammar = $this->connection->getQueryGrammar();

        $this->ensureFileBackedSqliteDatabase($config['database'], 'restored');
        $dropStatements = collect($schemaBuilder->getViews())
            ->pluck('name')
            ->map(fn (string $name): string => 'DROP VIEW IF EXISTS '.$queryGrammar->wrap($name).';')
            ->merge(collect($schemaBuilder->getTables())
                ->pluck('name')
                ->map(fn (string $name): string => 'DROP TABLE IF EXISTS '.$queryGrammar->wrap($name).';'))
            ->all();

        $dropSql = implode(PHP_EOL, [
            'PRAGMA foreign_keys=OFF;',
            ...$dropStatements,
        ]);

        return implode(' && ', [
            $this->resolveExecutable('sqlite3').' '.escapeshellarg((string) $config['database']).' '.escapeshellarg($dropSql),
            $this->resolveExecutable('sqlite3').' '.escapeshellarg((string) $config['database']).' < '.escapeshellarg($this->filePath),
        ]);
    }

    private function sqliteDotCommandArgument(string $value): string
    {
        return '"'.str_replace('"', '\\"', $value).'"';
    }

    private function ensureFileBackedSqliteDatabase(string $database, string $operation): void
    {
        if ($database !== '' && $database !== ':memory:' && ! str_starts_with($database, 'file::memory:')) {
            return;
        }

        throw new RuntimeException("SQLite databases can only be $operation when using a file-backed database connection.");
    }
}
