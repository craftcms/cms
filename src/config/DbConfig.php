<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\config;

use craft\helpers\StringHelper;
use yii\base\InvalidConfigException;
use yii\base\Object;

/**
 * DB config class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DbConfig extends Object
{
    // Constants
    // =========================================================================

    const DRIVER_MYSQL = 'mysql';
    const DRIVER_PGSQL = 'pgsql';

    // Properties
    // =========================================================================

    /**
     * @var array An array of key => value pairs of PDO attributes to pass into the PDO constructor.
     *
     * For example, when using the MySQL PDO driver (https://secure.php.net/manual/en/ref.pdo-mysql.php),
     * if you wanted to enable a SSL database connection (assuming SSL is enabled in MySQL
     * (https://dev.mysql.com/doc/refman/5.5/en/using-secure-connections.html) and `'user'`
     * can connect via SSL, you'd set these:
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
     * @var string The name of the database to select.
     */
    public $database = '';
    /**
     * @var string The database driver to use. Either 'mysql' for MySQL or 'pgsql' for PostgreSQL.
     */
    public $driver = self::DRIVER_MYSQL;
    /**
     * @var string If you want to manually specify your PDO DSN connection string you can do so here.
     *
     * - MySQL: https://secure.php.net/manual/en/ref.pdo-mysql.connection.php
     * - PostgreSQL: https://secure.php.net/manual/en/ref.pdo-pgsql.connection.php
     *
     * If you set this, then the [[server]], [[port]], [[user]], [[password]], [[database]],
     * [[driver]] and [[unixSocket]] config settings will be ignored.
     */
    public $dsn;
    /**
     * @var string The database password to connect with.
     */
    public $password = '';
    /**
     * @var int The database server port. Defaults to 3306 for MySQL and 5432 for PostgreSQL.
     */
    public $port;
    /**
     * @var string The database schema to use (PostgreSQL only).
     *
     * @see https://www.postgresql.org/docs/8.2/static/ddl-schemas.html
     */
    public $schema = 'public';
    /**
     * @var string The database server name or IP address. Usually 'localhost' or '127.0.0.1'.
     */
    public $server = 'localhost';
    /**
     * @var string If you're sharing Craft installs in a single database (MySQL) or a single
     * database and using a shared schema (PostgreSQL), then you can set a table
     * prefix here to avoid table naming conflicts per install. This can be no more than 5
     * characters, and must be all lowercase.
     */
    public $tablePrefix = '';
    /**
     * @var string|null MySQL only. If this is set, then the CLI connection string (used for yiic) will
     * connect to the Unix socket, instead of the server and port. If this is
     * specified, then 'server' and 'port' settings are ignored.
     */
    public $unixSocket;
    /**
     * @var string|null The database connection URL, if one was provided by your hosting environment.
     * If this is set, the values for [[driver]], [[user]], [[database]], [[server]], [[port]], and [[database]]
     * will be extracted from it.
     */
    public $url;
    /**
     * @var string The database username to connect with.
     */
    public $user = 'root';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // If $url was set, parse it to set other properties
        if ($this->url !== null) {
            $url = parse_url($this->url);
            if (isset($url['scheme'])) {
                $scheme = strtolower($url['scheme']);
                if (in_array($scheme, [self::DRIVER_PGSQL, 'postgres', 'postgresql'], true)) {
                    $this->driver = self::DRIVER_PGSQL;
                } else {
                    $this->driver = self::DRIVER_MYSQL;
                }
            }
            if (isset($url['user'])) {
                $this->user = $url['user'];
            }
            if (isset($url['pass'])) {
                $this->password = $url['pass'];
            }
            if (isset($url['host'])) {
                $this->server = $url['host'];
            }
            if (isset($url['port'])) {
                $this->port = $url['port'];
            }
            if (isset($url['path'])) {
                $this->database = trim($url['path'], '/');
            }
        }

        // Validate driver
        if (!in_array($this->driver, [self::DRIVER_MYSQL, self::DRIVER_PGSQL], true)) {
            throw new InvalidConfigException('Unsupported DB driver value: '.$this->driver);
        }

        // Validate tablePrefix
        if ($this->tablePrefix) {
            $this->tablePrefix = StringHelper::ensureRight($this->tablePrefix, '_');
            if (strlen($this->tablePrefix) > 6) {
                throw new InvalidConfigException('tablePrefix must be 5 or less characters long: '.$this->tablePrefix);
            }
        }

        // Lowercase server & unixSocket
        $this->server = strtolower($this->server);
        if ($this->unixSocket !== null) {
            $this->unixSocket = strtolower($this->unixSocket);
        }

        // Set the port
        if ($this->port === null || $this->port === '') {
            switch ($this->driver) {
                case self::DRIVER_MYSQL:
                    $this->port = 3306;
                    break;
                case self::DRIVER_PGSQL:
                    $this->port = 5432;
                    break;
            }
        } else {
            $this->port = (int)$this->port;
        }

        // Set the DSN
        if ($this->dsn === null || $this->dsn === '') {
            if ($this->driver === self::DRIVER_MYSQL && $this->unixSocket) {
                $this->dsn = "{$this->driver}:unix_socket={$this->unixSocket};dbname={$this->database};";
            } else {
                $this->dsn = "{$this->driver}:host={$this->server};dbname={$this->database};port={$this->port};";
            }
        }
    }
}
