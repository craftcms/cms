<?php

/**
 * @link https://github.com/yii2tech
 *
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Craft\Yii2Adapter;

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

        if (is_null($pdo = $this->getLaravelConnection()->getPdo())) {
            $this->getLaravelConnection()->reconnect();
            $pdo = $this->getLaravelConnection()->getPdo();
        }

        $this->pdo = $pdo;
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
        if ($this->server) {
            Config::set("database.connections.$this->driverName", array_merge(
                Config::array("database.connections.$this->driverName"),
                [
                    'host' => $this->server,
                    'port' => $this->port,
                    'database' => $this->database,
                    'username' => $this->username,
                    'password' => $this->password,
                    'prefix' => $this->tablePrefix,
                ]
            ));

            DB::purge($this->driverName);
        }

        $this->dsn = implode('', [
            $this->driverName,
            ':host=',
            Config::string("database.connections.{$this->driverName}.host"),
            ';port=',
            Config::string("database.connections.{$this->driverName}.port"),
            ';dbname=',
            Config::string("database.connections.{$this->driverName}.database"),
            ';user=',
            Config::string("database.connections.{$this->driverName}.username"),
            ';password=',
            Config::string("database.connections.{$this->driverName}.password"),
        ]);

        return DB::connection($this->driverName);
    }
}
