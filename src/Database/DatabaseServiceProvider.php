<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Database\Commands\MigrateCommand;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

final class DatabaseServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app
            ->when(Migrator::class)
            ->needs(MigrationRepositoryInterface::class)
            ->give(fn () => $this->app->make(MigrationRepository::class, ['table' => Table::MIGRATIONS]));

        $this->registerQueryBuilderMacros();
        $this->registerSchemaBuilderMacros();
    }

    public function boot(Repository $config, Connection $db, \Illuminate\Cache\Repository $cache): void
    {
        Aliases::set('@migrations', '@package/Database/Migrations');

        $this->commands([
            MigrateCommand::class,
        ]);

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
