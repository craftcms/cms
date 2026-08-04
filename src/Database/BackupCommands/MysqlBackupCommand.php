<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\BackupCommands;

use Symfony\Component\Process\Process;

use function CraftCms\Cms\normalizeVersion;

class MysqlBackupCommand extends BackupCommand
{
    public function backup(): string
    {
        $config = $this->getConnectionConfig();
        $executable = $this->resolveExecutable('mysqldump');
        $charset = (string) ($this->connection->getConfig('charset') ?? 'utf8mb4');

        $baseArgs = [
            $executable,
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
        ];

        if ($this->useSingleTransaction()) {
            $baseArgs[] = '--single-transaction';
        }

        if ($this->supportsColumnStatistics($executable)) {
            $baseArgs[] = '--column-statistics=0';
        }

        $schemaDump = implode(' ', [
            ...$baseArgs,
            '--no-data',
            '--skip-triggers',
            '--result-file='.escapeshellarg($this->filePath),
            escapeshellarg((string) $config['database']),
        ]);

        $dataArgs = [
            ...$baseArgs,
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

    private function useSingleTransaction(): bool
    {
        // https://bugs.mysql.com/bug.php?id=109685
        $serverVersion = normalizeVersion($this->connection->getServerVersion());

        return version_compare($serverVersion, '8', '>=') && version_compare($serverVersion, '8.0.32', '<');
    }

    private function supportsColumnStatistics(string $executable): bool
    {
        $pipe = PHP_OS_FAMILY === 'Windows' ? 'findstr' : 'grep';
        $process = Process::fromShellCommandline("$executable --help | $pipe \"column-statistics\"");
        $process->run();

        return $process->isSuccessful() && trim($process->getOutput()) !== '';
    }
}
