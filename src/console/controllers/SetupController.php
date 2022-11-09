<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Composer\Util\Platform;
use Craft;
use craft\config\DbConfig;
use craft\console\Controller;
use craft\db\Connection;
use craft\db\Table;
use craft\errors\DbConnectException;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\migrations\CreateDbCacheTable;
use craft\migrations\CreatePhpSessionTable;
use PDOException;
use Seld\CliPrompt\CliPrompt;
use Throwable;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;
use yii\db\Exception as DbException;

/**
 * Craft CMS setup installer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SetupController extends Controller
{
    /**
     * @var string|null The database driver to use. Either `'mysql'` for MySQL or `'pgsql'` for PostgreSQL.
     */
    public ?string $driver = null;
    /**
     * @var string|null The database server name or IP address. Usually `'localhost'` or `'127.0.0.1'`.
     */
    public ?string $server = null;
    /**
     * @var int|null The database server port. Defaults to 3306 for MySQL and 5432 for PostgreSQL.
     */
    public ?int $port = null;
    /**
     * @var string|null The database username to connect with.
     */
    public ?string $user = null;
    /**
     * @var string|null The database password to connect with.
     */
    public ?string $password = null;
    /**
     * @var string|null The name of the database to select.
     */
    public ?string $database = null;
    /**
     * @var string|null The schema that Postgres is configured to use by default (PostgreSQL only).
     * @see https://www.postgresql.org/docs/8.2/static/ddl-schemas.html
     */
    public ?string $schema = null;
    /**
     * @var string|null The table prefix to add to all database tables. This can
     * be no more than 5 characters, and must be all lowercase.
     */
    public ?string $tablePrefix = null;

    /**
     * @var bool Whether existing environment variables should be used as the default values by the `db-creds` command.
     * @see _env()
     */
    private bool $_useEnvDefaults = true;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if ($actionID === 'db-creds' || $actionID === 'db') {
            $options[] = 'driver';
            $options[] = 'server';
            $options[] = 'port';
            $options[] = 'user';
            $options[] = 'password';
            $options[] = 'database';
            $options[] = 'schema';
            $options[] = 'tablePrefix';
        }

        return $options;
    }

    /**
     * Sets up all the things.
     *
     * This is an interactive wrapper for the `setup/app-id`, `setup/security-key`, `setup/db-creds`,
     * and `install` commands, each of which support being run non-interactively.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $this->run('keys');

        if (!$this->interactive) {
            return ExitCode::OK;
        }

        $this->run('db-creds');

        if (Craft::$app->getIsInstalled(true)) {
            $this->stdout("It looks like Craft is already installed, so we're done here." . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        if (!$this->confirm(PHP_EOL . 'Install Craft now?', true)) {
            $this->stdout("You can install Craft from a browser once you've set up a web server, or by running this command:" . PHP_EOL, Console::FG_YELLOW);
            $this->_outputCommand('install');
            return ExitCode::OK;
        }

        $this->stdout(PHP_EOL);
        return $this->run('install/craft');
    }

    /**
     * Called from the `post-create-project-cmd` Composer hook.
     *
     * @return int
     */
    public function actionWelcome(): int
    {
        $craft = <<<EOD

   ______ .______          ___       _______ .___________.
  /      ||   _  \        /   \     |   ____||           |
 |  ,----'|  |_)  |      /  ^  \    |  |__   `---|  |----`
 |  |     |      /      /  /_\  \   |   __|      |  |
 |  `----.|  |\  \----./  _____  \  |  |         |  |
  \______|| _| `._____/__/     \__\ |__|         |__|
 
     A       N   E   W       I   N   S   T   A   L   L
               ______ .___  ___.      _______.
              /      ||   \/   |     /       |
             |  ,----'|  \  /  |    |   (----`
             |  |     |  |\/|  |     \   \
             |  `----.|  |  |  | .----)   |
              \______||__|  |__| |_______/



EOD;
        $this->stdout(str_replace("\n", PHP_EOL, $craft), Console::FG_YELLOW);

        // Can't do anything interactive here (https://github.com/composer/composer/issues/3299)
        $this->run('keys');
        $this->stdout(PHP_EOL . 'Welcome to Craft CMS!' . PHP_EOL . PHP_EOL);

        if (!$this->interactive || !$this->confirm('Are you ready to begin the setup?')) {
            $this->stdout('Run the following command if you want to setup Craft from your terminal:' . PHP_EOL);
            $this->_outputCommand('setup');
            return ExitCode::OK;
        }

        return $this->run('index');
    }

    /**
     * Generates an application ID and security key (if they don’t exist), and saves them in the `.env` file.
     *
     * @since 4.2.7
     * @return int
     */
    public function actionKeys(): int
    {
        $didSomething = false;

        if ((!Craft::$app->id || Craft::$app->id === 'CraftCMS') && !App::env('CRAFT_APP_ID')) {
            $this->run('app-id');
            $didSomething = true;
        }

        if (!Craft::$app->getConfig()->getGeneral()->securityKey) {
            $this->run('security-key');
            $didSomething = true;
        }

        if ($didSomething) {
            $this->stdout(PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * Generates a new application ID and saves it in the `.env` file.
     *
     * @return int
     * @since 3.4.25
     */
    public function actionAppId(): int
    {
        $this->stdout('Generating an application ID ... ', Console::FG_YELLOW);
        $key = 'CraftCMS--' . StringHelper::UUID();
        if (!$this->_setEnvVar('CRAFT_APP_ID', $key)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("done ($key)" . PHP_EOL, Console::FG_YELLOW);
        return ExitCode::OK;
    }

    /**
     * Generates a new security key and saves it in the `.env` file.
     *
     * @return int
     */
    public function actionSecurityKey(): int
    {
        $this->stdout('Generating a security key ... ', Console::FG_YELLOW);
        $key = Craft::$app->getSecurity()->generateRandomString();
        if (!$this->_setEnvVar('CRAFT_SECURITY_KEY', $key)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        Craft::$app->getConfig()->getGeneral()->securityKey = $key;
        $this->stdout("done ($key)" . PHP_EOL, Console::FG_YELLOW);
        return ExitCode::OK;
    }

    /**
     * Stores new DB connection settings to the `.env` file.
     *
     * @return int
     */
    public function actionDbCreds(): int
    {
        $badUserCredentials = false;

        top:

        // driver
        $envDriver = App::env('CRAFT_DB_DRIVER');
        $this->driver = $this->prompt('Which database driver are you using? (mysql or pgsql)', [
            'required' => true,
            'default' => $this->driver ?? $envDriver ?: 'mysql',
            'validator' => function(string $input) {
                return in_array($input, [Connection::DRIVER_MYSQL, Connection::DRIVER_PGSQL]);
            },
        ]);
        $this->_useEnvDefaults = !$envDriver || $envDriver === $this->driver;

        // server
        $this->server = $this->prompt('Database server name or IP address:', [
            'required' => true,
            'default' => $this->server ?? $this->_envDefault('CRAFT_DB_SERVER') ?? '127.0.0.1',
        ]);
        $this->server = strtolower($this->server);

        // port
        $this->port = (int)$this->prompt('Database port:', [
            'required' => true,
            'default' => $this->port ?? $this->_envDefault('CRAFT_DB_PORT') ?? ($this->driver === Connection::DRIVER_MYSQL ? 3306 : 5432),
            'validator' => function(string $input): bool {
                return is_numeric($input);
            },
        ]);

        userCredentials:

        // user & password
        $this->user = $this->prompt('Database username:', [
            'default' => $this->user ?? $this->_envDefault('CRAFT_DB_USER') ?? 'root',
        ]);

        if (!$this->password && $this->interactive) {
            $envPassword = App::env('CRAFT_DB_PASSWORD');
            if ($envPassword && $this->confirm('Use the password provided by $DB_PASSWORD?', true)) {
                $this->password = $envPassword;
            } else {
                $this->stdout('Database password: ');
                $this->password = CliPrompt::hiddenPrompt(true);
            }
        }

        /** @phpstan-ignore-next-line */
        if ($badUserCredentials) {
            $badUserCredentials = false;
            goto test;
        }

        // database
        if (!$this->interactive && !$this->database) {
            $this->stderr('The --database option must be set.' . PHP_EOL, Console::FG_RED);
            return ExitCode::USAGE;
        }
        $this->database = $this->prompt('Database name:', [
            'required' => true,
            'default' => $this->database ?? $this->_envDefault('CRAFT_DB_DATABASE') ?? null,
        ]);

        // tablePrefix
        $this->tablePrefix = $this->prompt('Database table prefix' . ($this->tablePrefix ? ' (type "none" for none)' : '') . ':', [
            'default' => $this->tablePrefix ?? $this->_envDefault('CRAFT_DB_TABLE_PREFIX') ?? null,
            'validator' => function(string $input): bool {
                if (strlen(StringHelper::ensureRight($input, '_')) > 6) {
                    $this->stderr('The table prefix must be 5 or less characters long.' . PHP_EOL, Console::FG_RED);
                    return false;
                }
                return true;
            },
        ]);
        if ($this->tablePrefix && $this->tablePrefix !== 'none') {
            $this->tablePrefix = StringHelper::ensureRight($this->tablePrefix, '_');
        } else {
            $this->tablePrefix = '';
        }

        // Test the DB connection
        $this->stdout('Testing database credentials ... ', Console::FG_YELLOW);

        test:

        /** @phpstan-ignore-next-line */
        if (!isset($dbConfig)) {
            try {
                $dbConfig = Craft::$app->getConfig()->getDb();
            } catch (InvalidConfigException) {
                $dbConfig = new DbConfig();
            }
        }

        $dbConfig->driver = $this->driver;
        $dbConfig->server = $this->server;
        $dbConfig->port = $this->port;
        $dbConfig->database = $this->database;
        $dbConfig->dsn = "$this->driver:host=$this->server;port=$this->port;dbname=$this->database;";
        $dbConfig->user = $this->user;
        $dbConfig->password = $this->password;
        $dbConfig->tablePrefix = $this->tablePrefix;

        $db = Craft::$app->getDb();
        $db->close();
        Craft::configure($db, ArrayHelper::without(App::dbConfig($dbConfig), 'class'));

        try {
            $db->open();
        } catch (DbConnectException $e) {
            // Error codes:
            // 7:    Name or service not known (server)
            // 7:    could not connect to server (port)
            // 7:    password authentication failed (username)
            // 7:    no password supplied (password)
            // 1045: Access denied for user (username, password)
            // 1049: Unknown database (database)
            // 2002: Connection timed out (server)
            /** @var PDOException $pdoException */
            $pdoException = $e->getPrevious()->getPrevious() ?? $e->getPrevious() ?? $e;
            $this->stderr('failed: ' . $pdoException->getMessage() . PHP_EOL, Console::FG_RED);

            // Test some common issues
            $message = $pdoException->getMessage();

            if ($this->server === 'localhost' && $message === 'SQLSTATE[HY000] [2002] No such file or directory') {
                // means the Unix socket doesn't exist - https://stackoverflow.com/a/22927341/1688568
                // try 127.0.0.1 instead...
                $this->stdout('Trying with 127.0.0.1 instead of localhost ... ', Console::FG_YELLOW);
                $this->server = '127.0.0.1';
                goto test;
            }

            if ($this->port === 3306 && $message === 'SQLSTATE[HY000] [2002] Connection refused') {
                // try 8889 instead (default MAMP port)...
                $this->stdout('Trying with port 8889 instead of 3306 ... ', Console::FG_YELLOW);
                $this->port = 8889;
                goto test;
            }

            if (
                str_contains($message, 'Access denied for user') ||
                str_contains($message, 'no password supplied') ||
                str_contains($message, 'password authentication failed for user')
            ) {
                $this->stdout('Try with a different username and/or password.' . PHP_EOL, Console::FG_YELLOW);
                $badUserCredentials = true;
                goto userCredentials;
            }

            if (!$this->interactive) {
                return ExitCode::UNSPECIFIED_ERROR;
            }

            goto top;
        }

        $this->stdout('success!' . PHP_EOL, Console::FG_GREEN);

        // Determine the default schema if Postgres
        if ($this->driver === Connection::DRIVER_PGSQL) {
            if ($dbConfig->setSchemaOnConnect) {
                $this->schema = $this->prompt('Database schema:', [
                    'required' => true,
                    'default' => $this->schema ?? App::env('CRAFT_DB_SCHEMA') ?? 'public',
                ]);
                $db->createCommand("SET search_path TO $this->schema;")->execute();
            } elseif ($this->schema === null) {
                // Make sure that the DB is actually configured to use the provided schema by default
                $searchPath = $db->createCommand('SHOW search_path')->queryScalar();
                $defaultSchemas = ArrayHelper::filterEmptyStringsFromArray(array_map('trim', explode(',', $searchPath))) ?: ['public'];

                // Get the available schemas (h/t https://dba.stackexchange.com/a/40051/205387)
                try {
                    $allSchemas = $db->createCommand('SELECT schema_name FROM information_schema.schemata')->queryColumn();
                } catch (DbException) {
                    try {
                        $allSchemas = $db->createCommand('SELECT nspname FROM pg_catalog.pg_namespace')->queryColumn();
                    } catch (DbException) {
                        $allSchemas = null;
                    }
                }

                if ($allSchemas !== null) {
                    // Use the first default schema that actually exists
                    foreach ($defaultSchemas as $schema) {
                        // "$user" => username
                        if ($schema === '"$user"') {
                            $schema = $this->user;
                        }

                        if (in_array($schema, $allSchemas)) {
                            $this->schema = $schema;
                            break;
                        }
                    }
                } else {
                    // Use the first non-user schema
                    foreach ($defaultSchemas as $schema) {
                        if ($schema !== '"$user"') {
                            $this->schema = $schema;
                            break;
                        }
                    }
                }

                if ($this->schema === null) {
                    // Assume 'public'
                    $this->schema = 'public';
                }

                $this->stdout('Using default schema "' . $this->schema . '".' . PHP_EOL, Console::FG_YELLOW);
            }
        }

        $db->getSchema()->defaultSchema = $this->schema;
        Craft::$app->setIsInstalled(null);

        $this->stdout('Saving database credentials to your .env file ... ', Console::FG_YELLOW);

        // If there's a DB_DSN environment variable, go with that
        if (App::env('CRAFT_DB_DSN') !== null) {
            if (!$this->_setEnvVar('CRAFT_DB_DSN', $dbConfig->dsn)) {
                return ExitCode::UNSPECIFIED_ERROR;
            }
        } elseif (
            !$this->_setEnvVar('CRAFT_DB_DRIVER', $this->driver) ||
            !$this->_setEnvVar('CRAFT_DB_SERVER', $this->server) ||
            !$this->_setEnvVar('CRAFT_DB_PORT', $this->port) ||
            !$this->_setEnvVar('CRAFT_DB_DATABASE', $this->database)
        ) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (
            !$this->_setEnvVar('CRAFT_DB_USER', $this->user) ||
            !$this->_setEnvVar('CRAFT_DB_PASSWORD', $this->password) ||
            !$this->_setEnvVar('CRAFT_DB_SCHEMA', $this->schema) ||
            !$this->_setEnvVar('CRAFT_DB_TABLE_PREFIX', $this->tablePrefix)
        ) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_YELLOW);
        return ExitCode::OK;
    }

    /**
     * Alias for setup/db-creds.
     *
     * @return int
     */
    public function actionDb(): int
    {
        return $this->actionDbCreds();
    }

    /**
     * Creates a database table for storing PHP session information.
     *
     * @return int
     * @since 3.4.0
     */
    public function actionPhpSessionTable(): int
    {
        if (Craft::$app->getDb()->tableExists(Table::PHPSESSIONS)) {
            $this->stdout('The `phpsessions` table already exists.' . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $migration = new CreatePhpSessionTable();
        if ($migration->up() === false) {
            $this->stderr('An error occurred while creating the `phpsessions` table.' . PHP_EOL . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('The `phpsessions` table was created successfully.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Creates a database table for storing DB caches.
     *
     * @return int
     * @since 3.4.14
     */
    public function actionDbCacheTable(): int
    {
        if (Craft::$app->getDb()->tableExists(Table::CACHE)) {
            $this->stdout('The `cache` table already exists.' . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $migration = new CreateDbCacheTable();
        if ($migration->up() === false) {
            $this->stderr('An error occurred while creating the `cache` table.' . PHP_EOL . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('The `cache` table was created successfully.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Outputs a terminal command.
     *
     * @param string $command
     */
    private function _outputCommand(string $command): void
    {
        $script = FileHelper::normalizePath($this->request->getScriptFile());
        if (!Platform::isWindows() && ($home = App::env('HOME')) !== null) {
            $home = FileHelper::normalizePath($home);
            if (str_starts_with($script, $home . DIRECTORY_SEPARATOR)) {
                $script = '~' . substr($script, strlen($home));
            }
        }
        $this->stdout(PHP_EOL . '    php ' . $script . ' ' . $command . PHP_EOL . PHP_EOL);
    }

    /**
     * Sets an environment variable value in the project’s `.env` file.
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    private function _setEnvVar(string $name, mixed $value): bool
    {
        $configService = Craft::$app->getConfig();
        $path = $configService->getDotEnvPath();

        if (!file_exists($path)) {
            if (!$this->interactive || $this->confirm(PHP_EOL . "A .env file doesn't exist at $path. Would you like to create one?", true)) {
                try {
                    FileHelper::writeToFile($path, '');
                } catch (Throwable $e) {
                    $this->stderr("Unable to create $path: {$e->getMessage()}" . PHP_EOL, Console::FG_RED);
                    return false;
                }

                $this->stdout("$path created. Note you still need to set up PHP dotenv for its values to take effect." . PHP_EOL, Console::FG_YELLOW);
            } else {
                $this->stdout(PHP_EOL . 'Action aborted.' . PHP_EOL, Console::FG_YELLOW);
                return false;
            }
        }

        try {
            $configService->setDotEnvVar($name, $value ?? '');
        } catch (Throwable $e) {
            $this->stderr("Unable to set $name on $path: {$e->getMessage()}" . PHP_EOL, Console::FG_RED);
            return false;
        }

        return true;
    }

    /**
     * Returns an environment variable value, if we are using them for defaults.
     *
     * @param string $name
     * @return string|null
     */
    private function _envDefault(string $name): ?string
    {
        return $this->_useEnvDefaults ? App::env($name) : null;
    }
}
