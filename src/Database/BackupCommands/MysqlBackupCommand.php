<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\BackupCommands;

class MysqlBackupCommand extends BackupCommand
{
    public function backup(): string
    {
        $config = $this->getConnectionConfig();
        $charset = (string) ($this->connection->getConfig('charset') ?? 'utf8mb4');

        $baseArgs = implode(' ', [
            $this->resolveExecutable('mysqldump'),
            '--defaults-file={defaultsFile}',
            '--add-drop-table',
            '--comments',
            '--create-options',
            '--dump-date',
            '--no-autocommit',
            '--routines',
            '--default-character-set='.$charset,
            '--set-charset',
            '--triggers',
            '--no-tablespaces',
        ]);

        $schemaDump = implode(' ', [
            $baseArgs,
            '--no-data',
            '--skip-triggers',
            '--result-file='.escapeshellarg($this->filePath),
            escapeshellarg((string) $config['database']),
        ]);

        $dataArgs = [
            $baseArgs,
            '--no-create-info',
        ];

        foreach ($this->ignoreTables as $table) {
            $raw = $this->tableName($table);
            $dataArgs[] = '--ignore-table='.escapeshellarg("{$config['database']}.$raw");
        }

        $dataArgs[] = escapeshellarg((string) $config['database']);

        return $schemaDump.' && '.implode(' ', $dataArgs).' >> '.escapeshellarg($this->filePath);
    }

    public function restore(): string
    {
        $config = $this->getConnectionConfig();

        return $this->resolveExecutable('mysql').' --defaults-file={defaultsFile} '.escapeshellarg((string) $config['database']).' < '.escapeshellarg($this->filePath);
    }
}
