<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\BackupCommands;

use CraftCms\Cms\Cms;
use Illuminate\Database\Connection;

class PostgresBackupCommand extends BackupCommand
{
    /** @param string[] $ignoreTables */
    public function __construct(
        Connection $connection,
        string $filePath,
        array $ignoreTables = [],
        private readonly ?string $backupFormat = null,
        private readonly ?string $restoreFormat = null,
    ) {
        parent::__construct($connection, $filePath, $ignoreTables);
    }

    public function backup(): string
    {
        $config = $this->getConnectionConfig();

        $parts = [
            $this->resolveExecutable('pg_dump'),
            '--dbname='.escapeshellarg((string) $config['database']),
            '--host='.escapeshellarg((string) $config['host']),
            '--port='.escapeshellarg((string) $config['port']),
            '--username='.escapeshellarg((string) $config['username']),
            '--if-exists',
            '--clean',
            '--no-owner',
            '--no-privileges',
            '--no-acl',
            '--file='.escapeshellarg($this->filePath),
            '--schema='.escapeshellarg((string) $config['schema']),
        ];

        foreach ($this->ignoreTables as $table) {
            $table = $this->tableName($table);
            $parts[] = '--exclude-table-data='.escapeshellarg("{$config['schema']}.$table");
        }

        $format = $this->backupFormat ?? Cms::config()->backupCommandFormat;

        if ($format) {
            $parts[] = '--format='.escapeshellarg($format);
        }

        return implode(' ', $parts);
    }

    public function restore(): string
    {
        $config = $this->getConnectionConfig();
        $usePgRestore = $this->restoreFormat !== null && $this->restoreFormat !== 'plain';

        $parts = [
            $this->resolveExecutable($usePgRestore ? 'pg_restore' : 'psql'),
            '--dbname='.escapeshellarg((string) $config['database']),
            '--host='.escapeshellarg((string) $config['host']),
            '--port='.escapeshellarg((string) $config['port']),
            '--username='.escapeshellarg((string) $config['username']),
            '--no-password',
        ];

        if ($usePgRestore) {
            $parts = array_merge($parts, [
                '--clean',
                '--if-exists',
                '--no-owner',
                '--no-acl',
                '--schema='.escapeshellarg((string) $config['schema']),
                '--single-transaction',
                escapeshellarg($this->filePath),
            ]);
        } else {
            $parts[] = '< '.escapeshellarg($this->filePath);
        }

        return implode(' ', $parts);
    }
}
