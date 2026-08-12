<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database;

use CraftCms\Cms\Support\File;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileFacade;
use ReflectionProperty;
use RequirementsChecker;
use RuntimeException;

class ConnectionConfig
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function normalize(array $config): array
    {
        if (($config['driver'] ?? null) !== 'sqlite') {
            return $config;
        }

        $database = (string) ($config['database'] ?? '');

        if ($database !== '') {
            $config['database'] = self::normalizeSqliteDatabasePath($database);
        }

        $config['foreign_key_constraints'] ??= true;

        if (self::isFileBackedSqliteDatabase($database)) {
            $config['busy_timeout'] ??= 5000;
            $config['journal_mode'] ??= 'wal';
            $config['pragmas'] = array_replace([
                'cache_size' => -20000,
                'mmap_size' => 2147483648,
                'temp_store' => 'MEMORY',
            ], $config['pragmas'] ?? []);
            $config['synchronous'] ??= 'normal';
        }

        unset(
            $config['host'],
            $config['port'],
            $config['username'],
            $config['password'],
            $config['schema'],
        );

        return $config;
    }

    public static function normalizeConfiguredConnections(Repository $config): void
    {
        foreach ($config->get('database.connections', []) as $name => $connectionConfig) {
            if (! is_array($connectionConfig)) {
                continue;
            }

            $config->set("database.connections.$name", self::normalize($connectionConfig));
        }
    }

    public static function normalizeSqliteDatabasePath(string $database): string
    {
        if ($database === ':memory:' || str_starts_with($database, 'file:')) {
            return $database;
        }

        return File::absolutePath($database, base_path());
    }

    public static function ensureSqliteDatabaseFile(string $database): void
    {
        if (! self::isFileBackedSqliteDatabase($database)) {
            return;
        }

        $database = self::normalizeSqliteDatabasePath($database);

        if (is_dir($database)) {
            throw new RuntimeException("SQLite database path [$database] is a directory.");
        }

        FileFacade::ensureDirectoryExists(dirname($database));

        if (FileFacade::exists($database)) {
            return;
        }

        FileFacade::put($database, '');
    }

    public static function requirementsChecker(?Connection $connection = null): RequirementsChecker
    {
        return self::configureRequirementsChecker(new RequirementsChecker, $connection);
    }

    public static function configureRequirementsChecker(RequirementsChecker $checker, ?Connection $connection = null): RequirementsChecker
    {
        $connection ??= DB::connection();

        $checker->dbDriver = $connection->getDriverName();
        $checker->dsn = self::dsn($connection);
        $checker->dbUser = (string) ($connection->getConfig('username') ?? '');
        $checker->dbPassword = (string) ($connection->getConfig('password') ?? '');

        return $checker;
    }

    public static function dsn(Connection $connection): string
    {
        if ($connection->getDriverName() === 'sqlite') {
            return 'sqlite:'.self::normalizeSqliteDatabasePath((string) $connection->getConfig('database'));
        }

        return implode('', [
            $connection->getDriverName(),
            ':host='.$connection->getConfig('host'),
            ';port='.$connection->getConfig('port'),
            ';dbname='.$connection->getConfig('database'),
            ';user='.$connection->getConfig('username'),
            ';password='.$connection->getConfig('password'),
        ]);
    }

    public static function isFileBackedSqliteDatabase(string $database): bool
    {
        return $database !== ''
            && $database !== ':memory:'
            && ! str_contains($database, '?mode=memory')
            && ! str_contains($database, '&mode=memory')
            && ! str_starts_with($database, 'file:');
    }

    public static function useDefaultConnectionForBulkOps(Connection $connection): void
    {
        config()->set('database.connections.db2', $connection->getConfig());
        $resolver = app(ConnectionResolverInterface::class);

        $connections = new ReflectionProperty($resolver, 'connections');
        $current = $connections->getValue($resolver);
        $current['db2'] = $connection;
        $connections->setValue($resolver, $current);
    }
}
