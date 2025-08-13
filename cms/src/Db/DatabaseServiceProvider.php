<?php

namespace CraftCms\Cms\Db;

use Illuminate\Cache\DatabaseStore;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

final class DatabaseServiceProvider extends ServiceProvider
{
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
