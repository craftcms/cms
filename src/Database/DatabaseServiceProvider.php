<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Database\Commands\BackupCommand;
use CraftCms\Cms\Database\Commands\ConvertCharsetCommand;
use CraftCms\Cms\Database\Commands\DropAllTablesCommand;
use CraftCms\Cms\Database\Commands\DropTablePrefixCommand;
use CraftCms\Cms\Database\Commands\MigrateCommand;
use CraftCms\Cms\Database\Commands\RepairCommand;
use CraftCms\Cms\Database\Commands\RestoreCommand;
use CraftCms\Cms\Element\BulkOp\BulkOpDeferrals;
use CraftCms\Cms\Element\BulkOp\BulkOps;
use CraftCms\Cms\Support\Query;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression as QueryExpression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Override;
use ReflectionProperty;

class DatabaseServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app
            ->when(Migrator::class)
            ->needs(MigrationRepositoryInterface::class)
            ->give(fn () => $this->app->make(MigrationRepository::class, ['table' => Table::MIGRATIONS]));

        $this->app
            ->when(BulkOps::class)
            ->needs(ConnectionInterface::class)
            ->give(fn () => $this->app->make(ConnectionResolverInterface::class)->connection(config('database.bulk_ops_connection', 'db2')));

        $this->app
            ->when(BulkOpDeferrals::class)
            ->needs(ConnectionInterface::class)
            ->give(fn () => $this->app->make(ConnectionResolverInterface::class)->connection(config('database.bulk_ops_connection', 'db2')));

        Connection::macro('isMysql', fn (bool $strict = false) => $strict ? $this->getDriverName() === 'mysql' : in_array($this->getDriverName(), ['mysql', 'mariadb']));
        Connection::macro('isMaria', fn () => $this->getDriverName() === 'mariadb');
        Connection::macro('isPgsql', fn () => $this->getDriverName() === 'pgsql');
        Connection::macro('isSqlite', fn () => $this->getDriverName() === 'sqlite');
        Connection::macro('driverLabel', fn () => match (true) {
            $this->isMaria() => 'MariaDB',
            $this->isMysql() => 'MySQL',
            $this->isSqlite() => 'SQLite',
            default => 'PostgreSQL',
        });

        Builder::macro('whereBool', fn ($column, bool $value) => $this->where($column, new QueryExpression(var_export($value, true))));
        Builder::macro('orWhereBool', fn ($column, bool $value) => $this->orWhere($column, new QueryExpression(var_export($value, true))));

        $this->registerQueryBuilderMacros();
        $this->registerSchemaBuilderMacros();
    }

    public function boot(Repository $config, Connection $db, \Illuminate\Cache\Repository $cache): void
    {
        Aliases::set('@migrations', '@package/Database/Migrations');

        $this->commands([
            BackupCommand::class,
            ConvertCharsetCommand::class,
            DropAllTablesCommand::class,
            DropTablePrefixCommand::class,
            MigrateCommand::class,
            RepairCommand::class,
            RestoreCommand::class,
        ]);

        if ($db->getDriverName() === 'sqlite') {
            $this->bootSqlite($config, $db, $cache);
        } else {
            $this->bootDefault($config, $db, $cache);
        }
    }

    private function bootSqlite(Repository $config, Connection $db, \Illuminate\Cache\Repository $cache): void
    {
        /**
         * For SQLite db2 must be the same connection as the default.
         */
        $config->set('database.connections.db2', $db->getConfig());
        $appDb = app(ConnectionResolverInterface::class);

        $connections = new ReflectionProperty($appDb, 'connections');
        $current = $connections->getValue($appDb);
        $current['db2'] = $db;
        $connections->setValue($appDb, $current);

        if ($cache->getStore() instanceof DatabaseStore) {
            $cache->getStore()->setConnection($db);
        }
    }

    private function bootDefault(Repository $config, Connection $db, \Illuminate\Cache\Repository $cache): void
    {
        /**
         * Register a second database connection to use during
         * bulk ops or when inside transactions.
         */
        $config->set('database.connections.db2', array_merge($db->getConfig(), [
            'name' => 'db2',
        ]));

        /**
         * Make sure the cache store uses the db2 connection
         * to prevent issues inside transactions.
         */
        if ($cache->getStore() instanceof DatabaseStore) {
            $cache->getStore()->setConnection(DB::connection('db2'));
        }
    }

    public function registerQueryBuilderMacros(): void
    {
        Builder::macro('whereParam', fn (string|Expression $column, mixed $param, string $defaultOperator = '=', bool $caseInsensitive = false, ?string $columnType = null, string $boolean = 'and'): Builder => Query::whereParam($this, $column, $param, $defaultOperator, $caseInsensitive, $columnType, $boolean));
        Builder::macro('orWhereParam', fn (string|Expression $column, mixed $param, string $defaultOperator = '=', bool $caseInsensitive = false, ?string $columnType = null): Builder => Query::whereParam($this, $column, $param, $defaultOperator, $caseInsensitive, $columnType, 'or'));
        Builder::macro('whereNumericParam', fn (string|Expression $column, mixed $param, string $defaultOperator = '=', ?string $columnType = Query::TYPE_INTEGER, string $boolean = 'and'): Builder => Query::whereNumericParam($this, $column, $param, $defaultOperator, $columnType, $boolean));
        Builder::macro('orWhereNumericParam', fn (string|Expression $column, mixed $param, string $defaultOperator = '=', ?string $columnType = Query::TYPE_INTEGER): Builder => Query::whereNumericParam($this, $column, $param, $defaultOperator, $columnType, 'or'));
        Builder::macro('whereDateParam', fn (string|Expression $column, mixed $param, string $defaultOperator = '=', string $boolean = 'and'): Builder => Query::whereDateParam($this, $column, $param, $defaultOperator, $boolean));
        Builder::macro('orWhereDateParam', fn (string|Expression $column, mixed $param, string $defaultOperator = '='): Builder => Query::whereDateParam($this, $column, $param, $defaultOperator, 'or'));
        Builder::macro('whereMoneyParam', fn (string|Expression $column, string $currency, mixed $param, string $defaultOperator = '=', string $boolean = 'and'): Builder => Query::whereMoneyParam($this, $column, $currency, $param, $defaultOperator, $boolean));
        Builder::macro('orWhereMoneyParam', fn (string|Expression $column, string $currency, mixed $param, string $defaultOperator = '='): Builder => Query::whereMoneyParam($this, $column, $currency, $param, $defaultOperator, 'or'));
        Builder::macro('whereBooleanParam', fn (string|Expression $column, mixed $param, ?bool $defaultValue = null, string $columnType = Query::TYPE_BOOLEAN, string $boolean = 'and'): Builder => Query::whereBooleanParam($this, $column, $param, $defaultValue, $columnType, $boolean));
        Builder::macro('orWhereBooleanParam', fn (string|Expression $column, mixed $param, ?bool $defaultValue = null, string $columnType = Query::TYPE_BOOLEAN): Builder => Query::whereBooleanParam($this, $column, $param, $defaultValue, $columnType, 'or'));

        Builder::macro('idByUid', fn (string $uid): ?int => (int) $this->where('uid', $uid)->value('id') ?: null);
        Builder::macro('idsByUids', fn (array $uids): array => $this->whereIn('uid', $uids)->pluck('id', 'uid')->all());
        Builder::macro('uidById', fn (int $id): ?string => $this->where('id', $id)->value('uid') ?: null);
        Builder::macro('uidsByIds', fn (array $ids): array => $this->whereIn('id', $ids)->pluck('uid', 'id')->all());

        Builder::macro('softDelete', function (?int $id = null): int {
            if (! is_null($id)) {
                $this->where($this->from.'.id', '=', $id);
            }

            return $this->update(['dateDeleted' => now()]);
        });

        Builder::macro('restore', function (?int $id = null): int {
            if (! is_null($id)) {
                $this->where($this->from.'.id', '=', $id);
            }

            return $this->update(['dateDeleted' => null]);
        });
    }

    private function registerSchemaBuilderMacros(): void
    {
        SchemaBuilder::macro('indexName', function (string $table, array|string|Expression $columns) {
            if ($this->getConnection()->getConfig('prefix_indexes')) {
                $table = str_contains($table, '.')
                    ? substr_replace($table, '.'.$this->getConnection()->getTablePrefix(), strrpos($table, '.'), 1)
                    : $this->getConnection()->getTablePrefix().$table;
            }

            $index = strtolower($table.'_'.implode('_', $columns));

            return 'idx_'.md5($index);
        });

        SchemaBuilder::macro('createIndex', function (string $table, array|string|Expression $columns, ?string $name = null, bool $unique = false): void {
            $this->table($table, function (Blueprint $table) use ($name, $columns, $unique) {
                $name ??= SchemaBuilder::indexName($table->getTable(), $columns);

                if ($unique) {
                    $table->unique($columns, $name);

                    return;
                }

                $table->index($columns, $name);
            });
        });
    }
}
