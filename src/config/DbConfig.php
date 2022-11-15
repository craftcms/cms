<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\config;

use Craft;
use craft\db\Connection;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\services\Config;
use yii\base\InvalidConfigException;

/**
 * DB config class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class DbConfig extends BaseConfig
{
    /**
     * @deprecated in 3.4.0. Use [[Connection::DRIVER_MYSQL]] instead.
     */
    public const DRIVER_MYSQL = 'mysql';

    /**
     * @deprecated in 3.4.0. Use [[Connection::DRIVER_PGSQL]] instead.
     */
    public const DRIVER_PGSQL = 'pgsql';

    /**
     * @inheritdoc
     */
    protected ?string $filename = Config::CATEGORY_DB;

    /**
     * @var array An array of key-value pairs of PDO attributes to pass into the PDO constructor.
     *
     * For example, when using the [MySQL PDO driver](https://php.net/manual/en/ref.pdo-mysql.php), if you wanted to enable a SSL database connection
     * (assuming [SSL is enabled in MySQL](https://dev.mysql.com/doc/mysql-secure-deployment-guide/5.7/en/secure-deployment-secure-connections.html) and `'user'` can connect via SSL,
     * you’d set these:
     *
     * ```php
     * ->attributes([
     *     PDO::MYSQL_ATTR_SSL_KEY => '/path/to/my/client-key.pem',
     *     PDO::MYSQL_ATTR_SSL_CERT => '/path/to/my/client-cert.pem',
     *     PDO::MYSQL_ATTR_SSL_CA => '/path/to/my/ca-cert.pem',
     * ])
     * ```
     */
    public array $attributes = [];

    /**
     * @var string The charset to use when creating tables.
     *
     * ::: tip
     * You can change the character set and collation across all existing database tables using this terminal command:
     *
     * ```bash
     * php craft db/convert-charset
     * ```
     * :::
     *
     * ::: code
     * ```php Static Config
     * ->charset('utf8mb4')
     * ```
     * ```shell Environment Override
     * CRAFT_DB_CHARSET=utf8mb4
     * ```
     * :::
     */
    public string $charset = 'utf8';

    /**
     * @var string|null The collation to use when creating tables.
     *
     * This is only used by MySQL. If null, the [[$charset|charset’s]] default collation will be used.
     *
     * | Charset   | Default collation    |
     * | --------- | -------------------- |
     * | `utf8`    | `utf8_general_ci`    |
     * | `utf8mb4` | `utf8mb4_0900_ai_ci` |
     *
     * ::: tip
     * You can change the character set and collation across all existing database tables using this terminal command:
     *
     * ```bash
     * php craft db/convert-charset
     * ```
     * :::
     *
     * ::: code
     * ```php Static Config
     * ->collation('utf8mb4_0900_ai_ci')
     * ```
     * ```shell Environment Override
     * CRAFT_DB_COLLATION=utf8mb4_0900_ai_ci
     * ```
     * :::
     *
     * @since 3.6.4
     */
    public ?string $collation = null;

    /**
     * @var string|null The Data Source Name (“DSN”) that tells Craft how to connect to the database.
     *
     * DSNs should begin with a driver prefix (`mysql:` or `pgsql:`), followed by driver-specific parameters.
     * For example, `mysql:host=127.0.0.1;port=3306;dbname=acme_corp`.
     *
     * - MySQL parameters: <https://php.net/manual/en/ref.pdo-mysql.connection.php>
     * - PostgreSQL parameters: <https://php.net/manual/en/ref.pdo-pgsql.connection.php>
     *
     * ::: code
     * ```php Static Config
     * ->dsn('mysql:host=127.0.0.1;port=3306;dbname=acme_corp')
     * ```
     * ```shell Environment Override
     * CRAFT_DB_DSN=mysql:host=127.0.0.1;port=3306;dbname=acme_corp
     * ```
     * :::
     */
    public ?string $dsn = null;

    /**
     * @var string The database password to connect with.
     *
     * ::: code
     * ```php Static Config
     * ->password('super-secret')
     * ```
     * ```shell Environment Override
     * CRAFT_DB_PASSWORD=super-secret
     * ```
     * :::
     */
    public string $password = '';

    /**
     * @var string|null The schema that Postgres is configured to use by default (PostgreSQL only).
     *
     * ::: tip
     * To force Craft to use the specified schema regardless of PostgreSQL’s `search_path` setting, you must enable
     * the [[setSchemaOnConnect]] setting.
     * :::
     *
     * ::: code
     * ```php Static Config
     * ->schema('myschema,public')
     * ```
     * ```shell Environment Override
     * CRAFT_DB_SCHEMA=myschema,public
     * ```
     * :::
     *
     * @see https://www.postgresql.org/docs/8.2/static/ddl-schemas.html
     */
    public ?string $schema = 'public';

    /**
     * @var bool Whether the [[schema]] should be explicitly used for database queries (PostgreSQL only).
     *
     * ::: warning
     * This will cause an extra `SET search_path` SQL query to be executed per database connection. Ideally,
     * PostgreSQL’s `search_path` setting should be configured to prioritize the desired schema.
     * :::
     *
     * ::: code
     * ```php Static Config
     * ->setSchemaOnConnect(true)
     * ```
     * ```shell Environment Override
     * CRAFT_DB_SET_SCHEMA_ON_CONNECT=true
     * ```
     * :::
     *
     * @since 3.7.27
     */
    public bool $setSchemaOnConnect = false;

    /**
     * @var string|null If you’re sharing Craft installs in a single database (MySQL) or a single database and using a shared schema (PostgreSQL),
     * you can set a table prefix here to avoid per-install table naming conflicts. This can be no more than 5 characters, and must be all lowercase.
     *
     * ::: code
     * ```php Static Config
     * ->tablePrefix('craft_')
     * ```
     * ```shell Environment Override
     * CRAFT_DB_TABLE_PREFIX=craft_
     * ```
     * :::
     */
    public ?string $tablePrefix = null;

    /**
     * @var string The database username to connect with.
     *
     * ::: code
     * ```php Static Config
     * ->user('db')
     * ```
     * ```shell Environment Override
     * CRAFT_DB_USER=db
     * ```
     * :::
     */
    public string $user = 'root';

    /**
     * @var bool Whether batched queries should be executed on a separate, unbuffered database connection.
     *
     * This setting only applies to MySQL. It can be enabled when working with high volume content, to prevent
     * PHP from running out of memory when querying too much data at once. (See
     * <https://www.yiiframework.com/doc/guide/2.0/en/db-query-builder#batch-query-mysql> for an explanation
     * of MySQL’s batch query limitations.)
     *
     * For more on Craft batch queries, see <https://craftcms.com/knowledge-base/query-batching-batch-each>.
     *
     * ::: code
     * ```php Static Config
     * ->useUnbufferedConnections(true)
     * ```
     * ```shell Environment Override
     * CRAFT_DB_USE_UNBUFFERED_CONNECTIONS=true
     * ```
     * :::
     *
     * @since 3.7.0
     */
    public bool $useUnbufferedConnections = false;

    /**
     * @var string|null The database connection URL, if one was provided by your hosting environment.
     *
     * If this is set, the values for [[driver]], [[user]], [[database]], [[server]], [[port]], and [[database]] will be extracted from it.
     *
     * ::: code
     * ```php Static Config
     * ->url('jdbc:mysql://database.foo:3306/mydb')
     * ```
     * ```shell Environment Override
     * CRAFT_DB_URL=jdbc:mysql://database.foo:3306/mydb
     * ```
     * :::
     */
    public ?string $url = null;

    /**
     * @var string|null The database driver to use. Either `mysql` for MySQL or `pgsql` for PostgreSQL.
     *
     * ::: code
     * ```php Static Config
     * ->driver('mysql')
     * ```
     * ```shell Environment Override
     * CRAFT_DB_DRIVER=mysql
     * ```
     * :::
     */
    public ?string $driver = null;

    /**
     * @var string|null The database server name or IP address. Usually `localhost` or `127.0.0.1`.
     *
     * ::: code
     * ```php Static Config
     * ->server('localhost')
     * ```
     * ```shell Environment Override
     * CRAFT_DB_SERVER=localhost
     * ```
     * :::
     */
    public ?string $server = null;

    /**
     * @var int|null The database server port. Defaults to 3306 for MySQL and 5432 for PostgreSQL.
     *
     * ::: code
     * ```php Static Config
     * ->port(3306)
     * ```
     * ```shell Environment Override
     * CRAFT_DB_PORT=3306
     * ```
     * :::
     */
    public ?int $port = null;

    /**
     * @var string|null MySQL only. If this is set, the CLI connection string (used for yiic) will connect to the Unix socket instead of
     * the server and port. If this is specified, then `server` and `port` settings are ignored.
     *
     * ::: code
     * ```php Static Config
     * ->unixSocket('/Applications/MAMP/tmp/mysql/mysql.sock')
     * ```
     * ```shell Environment Override
     * CRAFT_DB_UNIX_SOCKET=/Applications/MAMP/tmp/mysql/mysql.sock
     * ```
     * :::
     */
    public ?string $unixSocket = null;

    /**
     * @var string|null The name of the database to select.
     *
     * ::: code
     * ```php Static Config
     * ->database('mydatabase')
     * ```
     * ```shell Environment Override
     * CRAFT_DB_DATABASE=mydatabase
     * ```
     * :::
     */
    public ?string $database = null;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        // (Re-)normalize everything.
        $this
            ->url($this->url)
            ->tablePrefix($this->tablePrefix)
        ;

        // If we don't have a DSN yet, create one from the deprecated settings
        if (!isset($this->dsn)) {
            $this->_updateDsn();
        }
    }

    /**
     * An array of key-value pairs of PDO attributes to pass into the PDO constructor.
     *
     * For example, when using the [MySQL PDO driver](https://php.net/manual/en/ref.pdo-mysql.php), if you wanted to enable a SSL database connection
     * (assuming [SSL is enabled in MySQL](https://dev.mysql.com/doc/mysql-secure-deployment-guide/5.7/en/secure-deployment-secure-connections.html) and `'user'` can connect via SSL,
     * you’d set these:
     *
     * ```php
     * ->pdoAttributes([
     *     PDO::MYSQL_ATTR_SSL_KEY => '/path/to/my/client-key.pem',
     *     PDO::MYSQL_ATTR_SSL_CERT => '/path/to/my/client-cert.pem',
     *     PDO::MYSQL_ATTR_SSL_CA => '/path/to/my/ca-cert.pem',
     * ])
     * ```
     * @param array $value
     * @return self
     * @see $attributes
     * @since 4.2.0
     */
    public function pdoAttributes(array $value): self
    {
        $this->attributes = $value;
        return $this;
    }

    /**
     * The charset to use when creating tables.
     *
     * ::: tip
     * You can change the character set and collation across all existing database tables using this terminal command:
     *
     * ```bash
     * php craft db/convert-charset
     * ```
     * :::
     *
     * ```php
     * ->charset('utf8mb4')
     * ```
     *
     * @param string $value
     * @return self
     * @see $charset
     * @since 4.2.0
     */
    public function charset(string $value): self
    {
        $this->charset = $value;
        return $this;
    }

    /**
     * The collation to use when creating tables.
     *
     * This is only used by MySQL. If null, the [[$charset|charset’s]] default collation will be used.
     *
     * | Charset   | Default collation    |
     * | --------- | -------------------- |
     * | `utf8`    | `utf8_general_ci`    |
     * | `utf8mb4` | `utf8mb4_0900_ai_ci` |
     *
     * ::: tip
     * You can change the character set and collation across all existing database tables using this terminal command:
     *
     * ```bash
     * php craft db/convert-charset
     * ```
     * :::
     *
     * ```php
     * ->collation('utf8mb4_0900_ai_ci')
     * ```
     *
     * @param string|null $value
     * @return self
     * @see $collation
     * @since 4.2.0
     */
    public function collation(?string $value): self
    {
        $this->collation = $value;
        return $this;
    }

    /**
     * The Data Source Name (“DSN”) that tells Craft how to connect to the database.
     *
     * DSNs should begin with a driver prefix (`mysql:` or `pgsql:`), followed by driver-specific parameters.
     * For example, `mysql:host=127.0.0.1;port=3306;dbname=acme_corp`.
     *
     * - MySQL parameters: <https://php.net/manual/en/ref.pdo-mysql.connection.php>
     * - PostgreSQL parameters: <https://php.net/manual/en/ref.pdo-pgsql.connection.php>
     *
     * ```php
     * ->dsn('mysql:host=127.0.0.1;port=3306;dbname=acme_corp')
     * ```
     *
     * @param string|null $value
     * @return self
     * @see $dsn
     * @since 4.2.0
     */
    public function dsn(?string $value): self
    {
        $this->dsn = $value;

        if ($value) {
            $parsed = Db::parseDsn($value);
            $this->driver = $parsed['driver'];
            $this->unixSocket = $parsed['unix_socket'] ?? null;
            $this->server = $parsed['host'] ?? null;
            $this->port = (int)($parsed['port'] ?? 0) ?: null;
            $this->database = $parsed['dbname'] ?? null;
        }

        return $this;
    }

    /**
     * The database password to connect with.
     *
     * ```php
     * ->password('super-secret')
     * ```
     *
     * @param string $value
     * @return self
     * @see $password
     * @since 4.2.0
     */
    public function password(string $value): self
    {
        $this->password = $value;
        return $this;
    }

    /**
     * The schema that Postgres is configured to use by default (PostgreSQL only).
     *
     * ::: tip
     * To force Craft to use the specified schema regardless of PostgreSQL’s `search_path` setting, you must enable
     * the [[setSchemaOnConnect]] setting.
     * :::
     *
     * ```php
     * ->schema('myschema,public')
     * ```
     *
     * @param string|null $value
     * @return self
     * @see $schema
     * @see https://www.postgresql.org/docs/8.2/static/ddl-schemas.html
     * @since 4.2.0
     */
    public function schema(?string $value): self
    {
        $this->schema = $value;
        return $this;
    }

    /**
     * Whether the [[schema]] should be explicitly used for database queries (PostgreSQL only).
     *
     * ::: warning
     * This will cause an extra `SET search_path` SQL query to be executed per database connection. Ideally,
     * PostgreSQL’s `search_path` setting should be configured to prioritize the desired schema.
     * :::
     *
     * ```php
     * ->setSchemaOnConnect()
     * ```
     *
     * @param bool $value
     * @return self
     * @see $setSchemaOnConnect
     * @since 4.2.0
     */
    public function setSchemaOnConnect(bool $value = true): self
    {
        $this->setSchemaOnConnect = $value;
        return $this;
    }

    /**
     * If you’re sharing Craft installs in a single database (MySQL) or a single database and using a shared schema (PostgreSQL),
     * you can set a table prefix here to avoid per-install table naming conflicts. This can be no more than 5 characters, and must be all lowercase.
     *
     * ```php
     * ->tablePrefix('craft_')
     * ```
     *
     * @param string|null $value
     * @return self
     * @throws InvalidConfigException
     * @since 4.2.0
     * @see $tablePrefix
     */
    public function tablePrefix(?string $value): self
    {
        if ($value) {
            $value = StringHelper::ensureRight($value, '_');
            if (strlen($value) > 6) {
                throw new InvalidConfigException('tablePrefix must be 5 or less characters long: ' . $value);
            }
        }

        $this->tablePrefix = $value;
        return $this;
    }

    /**
     * The database username to connect with.
     *
     * ```php
     * ->user('db')
     * ```
     *
     * @param string $value
     * @return self
     * @see $user
     * @since 4.2.0
     */
    public function user(string $value): self
    {
        $this->user = $value;
        return $this;
    }

    /**
     * Whether batched queries should be executed on a separate, unbuffered database connection.
     *
     * This setting only applies to MySQL. It can be enabled when working with high volume content, to prevent
     * PHP from running out of memory when querying too much data at once. (See
     * <https://www.yiiframework.com/doc/guide/2.0/en/db-query-builder#batch-query-mysql> for an explanation
     * of MySQL’s batch query limitations.)
     *
     * For more on Craft batch queries, see <https://craftcms.com/knowledge-base/query-batching-batch-each>.
     *
     * ```php
     * ->useUnbufferedConnections()
     * ```
     *
     * @param bool $value
     * @return self
     * @see $useUnbufferedConnections
     * @since 4.2.0
     */
    public function useUnbufferedConnections(bool $value = true): self
    {
        $this->useUnbufferedConnections = $value;
        return $this;
    }

    /**
     * The database connection URL, if one was provided by your hosting environment.
     *
     * If this is set, the values for [[driver]], [[user]], [[database]], [[server]], [[port]], and [[database]] will be extracted from it.
     *
     * ```php
     * ->url('jdbc:mysql://database.foo:3306/mydb')
     * ```
     *
     * @param string|null $value
     * @return self
     * @see $url
     * @since 4.2.0
     */
    public function url(?string $value): self
    {
        if ($value) {
            Craft::configure($this, Db::url2config($value));
        }

        $this->url = $value;
        return $this;
    }

    /**
     * The database driver to use. Either `mysql` for MySQL or `pgsql` for PostgreSQL.
     *
     * ```php
     * ->driver('mysql')
     * ```
     *
     * @param string|null $value
     * @return self
     * @throws InvalidConfigException
     * @since 4.2.0
     * @see $driver
     */
    public function driver(?string $value): self
    {
        $this->driver = $value;
        $this->_updateDsn();
        return $this;
    }

    /**
     * The database server name or IP address. Usually `localhost` or `127.0.0.1`.
     *
     * ```php
     * ->server('localhost')
     * ```
     *
     * @param string|null $value
     * @return self
     * @throws InvalidConfigException
     * @since 4.2.0
     * @see $server
     */
    public function server(?string $value): self
    {
        $this->server = $value;
        $this->_updateDsn();
        return $this;
    }

    /**
     * The database server port. Defaults to 3306 for MySQL and 5432 for PostgreSQL.
     *
     * ```php
     * ->port(3306)
     * ```
     *
     * @param int|null $value
     * @return self
     * @throws InvalidConfigException
     * @since 4.2.0
     * @see $port
     */
    public function port(?int $value): self
    {
        $this->port = $value;
        $this->_updateDsn();
        return $this;
    }

    /**
     * MySQL only. If this is set, the CLI connection string (used for yiic) will connect to the Unix socket instead of
     * the server and port. If this is specified, then `server` and `port` settings are ignored.
     *
     * ```php
     * ->unixSocket('/Applications/MAMP/tmp/mysql/mysql.sock')
     * ```
     *
     * @param string|null $value
     * @return self
     * @throws InvalidConfigException
     * @since 4.2.0
     * @see $unixSocket
     */
    public function unixSocket(?string $value): self
    {
        $this->unixSocket = $value;
        $this->_updateDsn();
        return $this;
    }

    /**
     * The name of the database to select.
     *
     * ```php
     * ->database('mydatabase')
     * ```
     *
     * @param string|null $value
     * @return self
     * @throws InvalidConfigException
     * @since 4.2.0
     * @see $database
     */
    public function database(?string $value): self
    {
        $this->database = $value;
        $this->_updateDsn();
        return $this;
    }

    /**
     * Updates the DSN string based on the config setting values.
     *
     * @throws InvalidConfigException
     */
    private function _updateDsn(): void
    {
        if (!$this->driver) {
            $this->driver = Connection::DRIVER_MYSQL;
        }

        if (!in_array($this->driver, [Connection::DRIVER_MYSQL, Connection::DRIVER_PGSQL], true)) {
            throw new InvalidConfigException('Unsupported DB driver value: ' . $this->driver);
        }

        if ($this->driver === Connection::DRIVER_MYSQL && $this->unixSocket) {
            $this->unixSocket = strtolower($this->unixSocket);
            $this->dsn = "$this->driver:unix_socket=$this->unixSocket;dbname=$this->database";
            return;
        }

        $this->server = strtolower($this->server ?? '');

        if ($this->port) {
            $port = $this->port;
        } else {
            $port = match ($this->driver) {
                Connection::DRIVER_MYSQL => 3306,
                Connection::DRIVER_PGSQL => 5432,
            };
        }

        $this->dsn = "$this->driver:host=$this->server;dbname=$this->database;port=$port";
    }
}
