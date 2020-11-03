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
use yii\base\BaseObject;
use yii\base\InvalidConfigException;

/**
 * DB config class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class DbConfig extends BaseObject
{
    /**
     * @deprecated in 3.4.0. Use [[Connection::DRIVER_MYSQL]] instead.
     */
    const DRIVER_MYSQL = 'mysql';

    /**
     * @deprecated in 3.4.0. Use [[Connection::DRIVER_PGSQL]] instead.
     */
    const DRIVER_PGSQL = 'pgsql';

    /**
     * @var array An array of key => value pairs of PDO attributes to pass into the PDO constructor.
     *
     * For example, when using the [MySQL PDO driver](http://php.net/manual/en/ref.pdo-mysql.php), if you wanted to enable a SSL database connection
     * (assuming [SSL is enabled in MySQL](https://dev.mysql.com/doc/refman/5.5/en/using-secure-connections.html) and `'user'` can connect via SSL,
     * you’d set these:
     *
     * ```php
     * [
     *     PDO::MYSQL_ATTR_SSL_KEY    => '/path/to/my/client-key.pem',
     *     PDO::MYSQL_ATTR_SSL_CERT   => '/path/to/my/client-cert.pem',
     *     PDO::MYSQL_ATTR_SSL_CA     => '/path/to/my/ca-cert.pem',
     * ],
     * ```
     */
    public $attributes = [];

    /**
     * @var string The charset to use when creating tables.
     */
    public $charset = 'utf8';

    /**
     * @var string The Data Source Name (“DSN”) that tells Craft how to connect to the database.
     *
     * DSNs should begin with a driver prefix (`mysql:` or `pgsql:`), followed by driver-specific parameters.
     * For example, `mysql:host=127.0.0.1;port=3306;dbname=acme_corp`.
     *
     * - MySQL parameters: <https://php.net/manual/en/ref.pdo-mysql.connection.php>
     * - PostgreSQL parameters: <https://php.net/manual/en/ref.pdo-pgsql.connection.php>
     */
    public $dsn;

    /**
     * @var string The database password to connect with.
     */
    public $password = '';

    /**
     * @var string The schema that Postgres is configured to use by default (PostgreSQL only).
     * @see https://www.postgresql.org/docs/8.2/static/ddl-schemas.html
     */
    public $schema = 'public';

    /**
     * @var string If you’re sharing Craft installs in a single database (MySQL) or a single database and using a shared schema (PostgreSQL),
     * you can set a table prefix here to avoid per-install table naming conflicts. This can be no more than 5 characters, and must be all lowercase.
     */
    public $tablePrefix = '';

    /**
     * @var string The database username to connect with.
     */
    public $user = 'root';

    // Deprecated Properties
    // -------------------------------------------------------------------------

    /**
     * @var string|null The database connection URL, if one was provided by your hosting environment.
     *
     * If this is set, the values for [[driver]], [[user]], [[database]], [[server]], [[port]], and [[database]] will be extracted from it.
     */
    public $url;

    /**
     * @var string The database driver to use. Either 'mysql' for MySQL or 'pgsql' for PostgreSQL.
     */
    public $driver;

    /**
     * @var string The database server name or IP address. Usually `localhost` or `127.0.0.1`.
     */
    public $server;

    /**
     * @var int The database server port. Defaults to 3306 for MySQL and 5432 for PostgreSQL.
     */
    public $port;

    /**
     * @var string|null MySQL only. If this is set, the CLI connection string (used for yiic) will connect to the Unix socket instead of
     * the server and port. If this is specified, then `server` and `port` settings are ignored.
     */
    public $unixSocket;

    /**
     * @var string The name of the database to select.
     */
    public $database;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        // If $url was set, parse it to set other properties
        if ($this->url) {
            Craft::configure($this, Db::url2config($this->url));
        }

        // Validate tablePrefix
        if ($this->tablePrefix) {
            $this->tablePrefix = StringHelper::ensureRight($this->tablePrefix, '_');
            if (strlen($this->tablePrefix) > 6) {
                throw new InvalidConfigException('tablePrefix must be 5 or less characters long: ' . $this->tablePrefix);
            }
        }

        // If we don't have a DSN yet, create one from the deprecated settings
        if ($this->dsn === null) {
            $this->_updateDsn();
        }
    }

    /**
     * Updates the DSN string based on the config setting values.
     *
     * @throws InvalidConfigException if [[driver]] isn’t set to `mysql` or `pgsql`.
     * @deprecated in 3.4.0.
     */
    public function updateDsn()
    {
        $this->_updateDsn();
    }

    /**
     * Updates the DSN string based on the config setting values.
     *
     * @throws InvalidConfigException
     */
    private function _updateDsn()
    {
        if (!$this->driver) {
            $this->driver = Connection::DRIVER_MYSQL;
        }

        if (!in_array($this->driver, [Connection::DRIVER_MYSQL, Connection::DRIVER_PGSQL], true)) {
            throw new InvalidConfigException('Unsupported DB driver value: ' . $this->driver);
        }

        if ($this->driver === Connection::DRIVER_MYSQL && $this->unixSocket) {
            $this->unixSocket = strtolower($this->unixSocket);
            $this->dsn = "{$this->driver}:unix_socket={$this->unixSocket};dbname={$this->database}";
            return;
        }

        $this->server = strtolower($this->server ?? '');
        if ($this->port === null || $this->port === '') {
            switch ($this->driver) {
                case Connection::DRIVER_MYSQL:
                    $this->port = 3306;
                    break;
                case Connection::DRIVER_PGSQL:
                    $this->port = 5432;
                    break;
            }
        }
        $this->dsn = "{$this->driver}:host={$this->server};dbname={$this->database};port={$this->port}";
    }
}
