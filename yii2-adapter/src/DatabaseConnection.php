<?php

/**
 * @link https://github.com/yii2tech
 *
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace CraftCms\Yii2Adapter;

use Illuminate\Database\Connection as IlluminateConnection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use yii\db\Connection;

class DatabaseConnection extends Connection
{
    public ?string $server;

    public ?int $port;

    public ?string $database;

    /**
     * {@inheritdoc}
     */
    public function open(): void
    {
        if ($this->pdo !== null) {
            return;
        }

        $this->getLaravelConnection()->reconnectIfMissingConnection();

        $this->pdo = $this->getLaravelConnection()->getPdo();
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        $this->getLaravelConnection()->disconnect();

        parent::close();
    }

    public function getLaravelConnection(): IlluminateConnection
    {
        $this->dsn = implode('', [
            $this->driverName,
            ':host=',
            Config::get("database.connections.{$this->driverName}.host"),
            ';port=',
            Config::get("database.connections.{$this->driverName}.port"),
            ';dbname=',
            Config::get("database.connections.{$this->driverName}.database"),
            ';user=',
            Config::get("database.connections.{$this->driverName}.username"),
            ';password=',
            Config::get("database.connections.{$this->driverName}.password"),
        ]);

        return DB::connection($this->driverName);
    }
}
