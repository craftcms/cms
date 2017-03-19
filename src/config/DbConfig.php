<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\config;

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
     * @var string The database server name or IP address. Usually 'localhost' or '127.0.0.1'.
     */
    public $server = 'localhost';
    /**
     * @var int|null The database server port. Defaults to 3306 for MySQL and 5432 for PostgreSQL.
     */
    public $port;
    /**
     * @var string The database username to connect with.
     */
    public $user = 'root';
    /**
     * @var string The database password to connect with.
     */
    public $password = '';
    /**
     * @var string The name of the database to select.
     */
    public $database = '';
    /**
     * @var string The database driver to use. Either 'mysql' for MySQL or 'pgsql' for PostgreSQL.
     */
    public $driver = self::DRIVER_MYSQL;
    /**
     * @var string The database schema to use (PostgreSQL only).
     *
     * @see https://www.postgresql.org/docs/8.2/static/ddl-schemas.html
     */
    public $schema = 'public';
    /**
     * @var string If you're sharing Craft installs in a single database (MySQL) or a single
     * database and using a shared schema (PostgreSQL), then you can set a table
     * prefix here to avoid table naming conflicts per install. This can be no more than 5
     * characters, and must be all lowercase.
     */
    public $tablePrefix = '';
    /**
     * @var string The charset to use when creating tables.
     */
    public $charset = 'utf8';
    /**
     * @var string|null MySQL only. If this is set, then the CLI connection string (used for yiic) will
     * connect to the Unix socket, instead of the server and port. If this is
     * specified, then 'server' and 'port' settings are ignored.
     */
    public $unixSocket;
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
     * @var string|null If you want to manually specify your PDO DSN connection string you can do so here.
     *
     * - MySQL: https://secure.php.net/manual/en/ref.pdo-mysql.connection.php
     * - PostgreSQL: https://secure.php.net/manual/en/ref.pdo-pgsql.connection.php
     *
     * If you set this, then the [[server]], [[port]], [[user]], [[password]], [[database]],
     * [[driver]] and [[unixSocket]] config settings will be ignored.
     */
    public $dsn;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Validate driver
        if (!in_array($this->driver, [self::DRIVER_MYSQL, self::DRIVER_PGSQL], true)) {
            throw new InvalidConfigException('Unsupported DB driver value: '.$this->driver);
        }

        // Validate tablePrefix
        $this->tablePrefix = rtrim($this->tablePrefix, '_');
        if (strlen($this->tablePrefix) > 5) {
            throw new InvalidConfigException('tablePrefix must be 5 or less characters long: '.$this->tablePrefix);
        }
    }
}
