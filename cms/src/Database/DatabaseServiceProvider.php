<?php

namespace CraftCms\Cms\Database;

use Illuminate\Cache\DatabaseStore;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

/**
 * @since 6.0.0
 */
final class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
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

    public function boot(Repository $config, Connection $db, \Illuminate\Cache\Repository $cache): void
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
        $store = $cache->getStore();
        if ($store instanceof DatabaseStore) {
            $store->setConnection(DB::connection('db2'));
        }
    }
}
