<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\config;

use Codeception\Test\Unit;
use craft\config\DbConfig;
use craft\db\Connection;

/**
 * Unit tests for DbConfig
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.2.1
 */
class DbConfigTest extends Unit
{
    /**
     * Test DbConfig::dsn()
     */
    public function testDsn(): void
    {
        $config = DbConfig::create()
            ->driver(Connection::DRIVER_MYSQL)
            ->unixSocket('/foo/bar/mysql.sock')
            ->database('db');
        $this->assertEquals("mysql:unix_socket=/foo/bar/mysql.sock;dbname=db", $config->dsn);

        $config = DbConfig::create()
            ->driver(Connection::DRIVER_MYSQL)
            ->server('127.0.0.1')
            ->database('db');
        $this->assertEquals("mysql:host=127.0.0.1;dbname=db;port=3306", $config->dsn);

        $config = DbConfig::create()
            ->driver(Connection::DRIVER_PGSQL)
            ->server('127.0.0.1')
            ->database('db');
        $this->assertEquals("pgsql:host=127.0.0.1;dbname=db;port=5432", $config->dsn);

        $config = DbConfig::create()
            ->dsn("pgsql:host=127.0.0.1;dbname=db;port=5432")
            ->database('db2');
        $this->assertEquals("pgsql:host=127.0.0.1;dbname=db2;port=5432", $config->dsn);
        $this->assertEquals('pgsql', $config->driver);
        $this->assertEquals('127.0.0.1', $config->server);
        $this->assertEquals('db2', $config->database);
        $this->assertEquals(5432, $config->port);
    }

    /**
     * Test DbConfig::url()
     */
    public function testUrl(): void
    {
        $config = DbConfig::create()
            ->url('mysql://127.0.0.1:3306/db');
        $this->assertEquals('db', $config->database);
        $this->assertEquals("mysql:host=127.0.0.1;dbname=db;port=3306", $config->dsn);

        $config = DbConfig::create()
            ->url('pgsql://127.0.0.1:5432/db');
        $this->assertEquals('db', $config->database);
        $this->assertEquals("pgsql:host=127.0.0.1;dbname=db;port=5432", $config->dsn);

        $config = DbConfig::create()
            ->url('pgsql://127.0.0.1:5432/db')
            ->database('db2');
        $this->assertEquals('db2', $config->database);
        $this->assertEquals("pgsql:host=127.0.0.1;dbname=db2;port=5432", $config->dsn);
    }
}
