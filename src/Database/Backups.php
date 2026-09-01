<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\BackupCommands\MysqlBackupCommand;
use CraftCms\Cms\Database\BackupCommands\PostgresBackupCommand;
use CraftCms\Cms\Database\BackupCommands\SqliteBackupCommand;
use CraftCms\Cms\Database\Events\BackupCreated;
use CraftCms\Cms\Database\Events\BackupCreating;
use CraftCms\Cms\Database\Events\BackupRestored;
use CraftCms\Cms\Database\Events\BackupRestoring;
use CraftCms\Cms\Database\Exceptions\CommandFailedException;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;

#[Singleton]
final readonly class Backups
{
    private const array DEFAULT_IGNORED_TABLES = [
        Table::ASSETINDEXDATA,
        Table::CACHE,
        Table::IMAGETRANSFORMINDEX,
        Table::RESOURCEPATHS,
        Table::PHPSESSIONS,
        Table::SESSIONS,
    ];

    public function __construct(
        private GeneralConfig $generalConfig,
    ) {}

    public function getBackupFilePath(?Connection $connection = null, ?string $backupFormat = null): string
    {
        $connection ??= DB::connection();
        $systemName = File::sanitizeFilename(Cms::systemName(), ['asciiOnly' => true]);
        $systemName = str_replace(['\'', '"'], '', strtolower($systemName));
        $version = Info::fetch()->version ?? Cms::VERSION;
        $filename = ($systemName ? "$systemName--" : '').now('UTC')->format('Y-m-d-His')."--v$version";
        $backupPath = Path::dbBackup();
        $path = $backupPath.DIRECTORY_SEPARATOR.$filename.$this->dumpExtension($connection, $backupFormat);

        $i = 0;
        while (file_exists($path)) {
            $path = $backupPath.DIRECTORY_SEPARATOR.$filename.'--'.++$i.$this->dumpExtension($connection, $backupFormat);
        }

        return $path;
    }

    /** @param string[]|null $ignoreTables */
    public function backup(
        ?Connection $connection = null,
        ?string $backupFormat = null,
        ?array $ignoreTables = null,
    ): string {
        $connection ??= DB::connection();
        $file = $this->getBackupFilePath($connection, $backupFormat);
        $this->backupTo(
            filePath: $file,
            connection: $connection,
            backupFormat: $backupFormat,
            ignoreTables: $ignoreTables,
        );

        return $file;
    }

    /** @param string[]|null $ignoreTables */
    public function backupTo(
        string $filePath,
        ?Connection $connection = null,
        ?string $backupFormat = null,
        ?array $ignoreTables = null,
    ): void {
        $connection ??= DB::connection();
        $ignoreTables ??= self::DEFAULT_IGNORED_TABLES;

        event($event = new BackupCreating(
            connection: $connection,
            file: $filePath,
            ignoreTables: $ignoreTables,
        ));

        $command = $this->resolveBackupCommand(
            connection: $connection,
            filePath: $filePath,
            backupFormat: $backupFormat,
            ignoreTables: $event->ignoreTables ?? [],
        );

        $this->executeCommandWithMysqlDefaults($connection, $command);

        event(new BackupCreated(
            connection: $connection,
            file: $filePath,
        ));

        $this->cycleBackups($connection, $backupFormat);
    }

    public function restore(
        string $filePath,
        ?Connection $connection = null,
        ?string $restoreFormat = null,
    ): void {
        $connection ??= DB::connection();

        event(new BackupRestoring(
            connection: $connection,
            file: $filePath,
        ));

        $command = $this->resolveRestoreCommand($connection, $filePath, $restoreFormat);

        $this->executeCommandWithMysqlDefaults($connection, $command);

        event(new BackupRestored(
            connection: $connection,
            file: $filePath,
        ));
    }

    /** @param string[] $ignoreTables */
    private function resolveBackupCommand(Connection $connection, string $filePath, ?string $backupFormat, array $ignoreTables): string
    {
        return $this->resolveCommand(
            connection: $connection,
            filePath: $filePath,
            configCommand: $this->generalConfig->backupCommand,
            defaultCommandGenerator: fn () => $this->defaultBackupCommand(
                connection: $connection,
                filePath: $filePath,
                backupFormat: $backupFormat,
                ignoreTables: $ignoreTables,
            ),
            errorMessage: 'Database not backed up because the backup command is false.',
        );
    }

    private function resolveRestoreCommand(Connection $connection, string $filePath, ?string $restoreFormat): string
    {
        return $this->resolveCommand(
            connection: $connection,
            filePath: $filePath,
            configCommand: $this->generalConfig->restoreCommand,
            defaultCommandGenerator: fn () => $this->defaultRestoreCommand(
                connection: $connection,
                filePath: $filePath,
                restoreFormat: $restoreFormat,
            ),
            errorMessage: 'Database not restored because the restore command is false.',
        );
    }

    private function resolveCommand(
        Connection $connection,
        string $filePath,
        bool|string|Closure|null $configCommand,
        Closure $defaultCommandGenerator,
        string $errorMessage,
    ): string {
        if ($configCommand === false) {
            throw new RuntimeException($errorMessage);
        }

        if (is_string($configCommand)) {
            return $this->replaceCommandTokens($connection, $configCommand, $filePath);
        }

        $command = $defaultCommandGenerator();

        if ($configCommand instanceof Closure) {
            return $this->replaceCommandTokens(
                $connection,
                $this->applyCommandClosure($configCommand, $command),
                $filePath,
            );
        }

        return $command;
    }

    /** @param string[] $ignoreTables */
    private function defaultBackupCommand(Connection $connection, string $filePath, ?string $backupFormat, array $ignoreTables): string
    {
        return match (true) {
            $connection->isPgsql() => new PostgresBackupCommand($connection, $filePath, $ignoreTables, $backupFormat)->backup(),
            $connection->isMysql() => new MysqlBackupCommand($connection, $filePath, $ignoreTables)->backup(),
            $connection->isSqlite() => new SqliteBackupCommand($connection, $filePath, $ignoreTables)->backup(),
            default => throw new RuntimeException('Database backups are only supported for MySQL/MariaDB, PostgreSQL, and SQLite.'),
        };
    }

    private function defaultRestoreCommand(Connection $connection, string $filePath, ?string $restoreFormat): string
    {
        return match (true) {
            $connection->isPgsql() => new PostgresBackupCommand($connection, $filePath, restoreFormat: $restoreFormat)->restore(),
            $connection->isMysql() => new MysqlBackupCommand($connection, $filePath)->restore(),
            $connection->isSqlite() => new SqliteBackupCommand($connection, $filePath)->restore(),
            default => throw new RuntimeException('Database restore is only supported for MySQL/MariaDB, PostgreSQL, and SQLite.'),
        };
    }

    private function applyCommandClosure(Closure $closure, string $command): string
    {
        $result = $closure($command);

        if (! is_string($result) || $result === '') {
            throw new RuntimeException('Custom backup/restore command closure must return a non-empty string.');
        }

        return $result;
    }

    private function replaceCommandTokens(Connection $connection, string $command, string $filePath): string
    {
        $config = $this->getConnectionConfig($connection);

        $tokens = [
            '{file}' => $filePath,
            '{port}' => $config['port'],
            '{server}' => $config['host'],
            '{user}' => $config['username'],
            '{password}' => str_replace("'", "'\"'\"'", $config['password']),
            '{database}' => $config['database'],
            '{schema}' => $config['schema'],
        ];

        return str_replace(array_keys($tokens), $tokens, $command);
    }

    /** @return array<string, string> */
    private function processEnv(Connection $connection): array
    {
        if (! $connection->isPgsql()) {
            return [];
        }

        $config = $this->getConnectionConfig($connection);

        return [
            'PGPASSWORD' => $config['password'],
        ];
    }

    /** @param array<string, string> $env */
    private function runShellCommand(string $command, array $env = []): void
    {
        $process = Process::fromShellCommandline($command, env: $env ?: null);
        $process->setTimeout(null);
        $process->run();

        if ($process->isSuccessful()) {
            return;
        }

        throw new CommandFailedException(
            command: preg_replace('/PGPASSWORD=(\S+)/i', 'PGPASSWORD=••••••', $command) ?? $command,
            exitCode: $process->getExitCode() ?? 1,
            error: trim($process->getErrorOutput()) ?: null,
        );
    }

    private function createMysqlDefaultsFile(Connection $connection): string
    {
        $path = File::normalizePath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.uniqid('craft-db-', true).'.cnf';
        $config = $this->getConnectionConfig($connection);
        $socket = (string) ($connection->getConfig('unix_socket') ?? '');

        $lines = [
            '[client]',
            'user='.$config['username'],
            'password="'.addslashes((string) $config['password']).'"',
        ];

        if ($socket !== '') {
            $lines[] = 'socket='.$socket;
        } else {
            $lines[] = 'host='.$config['host'];
            $lines[] = 'port='.$config['port'];
        }

        file_put_contents($path, implode(PHP_EOL, $lines));
        chmod($path, 0600);

        return $path;
    }

    private function cycleBackups(Connection $connection, ?string $backupFormat): void
    {
        $maxBackups = $this->generalConfig->maxBackups;
        if (! $maxBackups) {
            return;
        }

        $backupPath = Path::dbBackup();
        $extension = $this->dumpExtension($connection, $backupFormat);

        /** @var string[] $files */
        $files = array_merge(
            glob($backupPath.DIRECTORY_SEPARATOR."*$extension") ?: [],
            glob($backupPath.DIRECTORY_SEPARATOR."*$extension.zip") ?: [],
        );

        usort($files, static fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

        foreach (array_slice($files, $maxBackups) as $backupToDelete) {
            File::delete($backupToDelete);
        }
    }

    private function dumpExtension(Connection $connection, ?string $backupFormat): string
    {
        $format = $connection->isPgsql()
            ? ($backupFormat ?? $this->generalConfig->backupCommandFormat)
            : null;

        return match ($format) {
            'custom', 'directory' => '.dump',
            'tar' => '.tar',
            default => '.sql',
        };
    }

    /** @return array{database: string, host: string, port: string, username: string, password: string, schema: string} */
    private function getConnectionConfig(Connection $connection): array
    {
        return [
            'database' => (string) ($connection->getConfig('database') ?? ''),
            'host' => (string) ($connection->getConfig('host') ?? ''),
            'port' => (string) ($connection->getConfig('port') ?? ''),
            'username' => (string) ($connection->getConfig('username') ?? ''),
            'password' => (string) ($connection->getConfig('password') ?? ''),
            'schema' => (string) ($connection->getConfig('schema') ?? 'public'),
        ];
    }

    private function executeCommandWithMysqlDefaults(Connection $connection, string $command): void
    {
        $tempFile = null;

        if (($connection->isMysql() || $connection->isMaria()) && str_contains($command, '{defaultsFile}')) {
            $tempFile = $this->createMysqlDefaultsFile($connection);
            $command = str_replace('{defaultsFile}', escapeshellarg($tempFile), $command);
        }

        try {
            $this->runShellCommand($command, $this->processEnv($connection));
        } finally {
            if ($tempFile && is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
    }
}
