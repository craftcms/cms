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
use Seld\CliPrompt\CliPrompt;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;

/**
 * Craft CMS setup installer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SetupController extends Controller
{
    /**
     * @var string|null The database driver to use. Either 'mysql' for MySQL or 'pgsql' for PostgreSQL.
     */
    public $driver;
    /**
     * @var string|null The database server name or IP address. Usually 'localhost' or '127.0.0.1'.
     */
    public $server;
    /**
     * @var int|null The database server port. Defaults to 3306 for MySQL and 5432 for PostgreSQL.
     */
    public $port = 0;
    /**
     * @var string|null The database username to connect with.
     */
    public $user = 'root';
    /**
     * @var string|null The database password to connect with.
     */
    public $password;
    /**
     * @var string|null The name of the database to select.
     */
    public $database;
    /**
     * @var string|null The database schema to use (PostgreSQL only).
     * @see https://www.postgresql.org/docs/8.2/static/ddl-schemas.html
     */
    public $schema;
    /**
     * @var string|null The table prefix to add to all database tables. This can
     * be no more than 5 characters, and must be all lowercase.
     */
    public $tablePrefix;

    /**
     * @inheritdoc
     */
    public function options($actionID)
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
     * @return int
     */
    public function actionIndex(): int
    {
        if (Craft::$app->id === 'CraftCMS' && !App::env('APP_ID')) {
            $this->run('app-id');
            $this->stdout(PHP_EOL);
        }

        if (!Craft::$app->getConfig()->getGeneral()->securityKey) {
            $this->run('security-key');
            $this->stdout(PHP_EOL);
        }

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
        return $this->module->runAction('install');
    }

    /**
     * Called from the post-create-project-cmd Composer hook.
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
        $this->run('app-id');
        $this->run('security-key');
        $this->stdout(PHP_EOL . 'Welcome to Craft CMS!' . PHP_EOL . PHP_EOL);

        if (!$this->interactive || !$this->confirm('Are you ready to begin the setup?')) {
            $this->stdout('Run the following command if you want to setup Craft from your terminal:' . PHP_EOL);
            $this->_outputCommand('setup');
            return ExitCode::OK;
        }

        return $this->run('index');
    }

    /**
     * Generates a new application ID and saves it in the .env file.
     *
     * @return int
     * @since 3.4.25
     */
    public function actionAppId(): int
    {
        $this->stdout('Generating an application ID ... ', Console::FG_YELLOW);
        $key = 'CraftCMS--' . StringHelper::UUID();
        if (!$this->_setEnvVar('APP_ID', $key)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("done ({$key})" . PHP_EOL, Console::FG_YELLOW);
        return ExitCode::OK;
    }

    /**
     * Generates a new security key and saves it in the .env file.
     *
     * @return int
     */
    public function actionSecurityKey(): int
    {
        $this->stdout('Generating a security key ... ', Console::FG_YELLOW);
        $key = Craft::$app->getSecurity()->generateRandomString();
        if (!$this->_setEnvVar('SECURITY_KEY', $key)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        Craft::$app->getConfig()->getGeneral()->securityKey = $key;
        $this->stdout("done ({$key})" . PHP_EOL, Console::FG_YELLOW);
        return ExitCode::OK;
    }

    /**
     * Stores new DB connection settings to the .env file.
     *
     * @return int
     */
    public function actionDbCreds(): int
    {
        $badUserCredentials = false;
        $isNitro = App::isNitro();

        top:

        // driver
        if ($this->driver) {
            if (!in_array($this->driver, [Connection::DRIVER_MYSQL, Connection::DRIVER_PGSQL], true)) {
                $this->stderr('--driver must be either "' . Connection::DRIVER_MYSQL . '" or "' . Connection::DRIVER_PGSQL . '".' . PHP_EOL, Console::FG_RED);
                return ExitCode::USAGE;
            }
        } else if ($this->interactive) {
            $this->driver = $this->select('Which database driver are you using?', [
                Connection::DRIVER_MYSQL => 'MySQL',
                Connection::DRIVER_PGSQL => 'PostgreSQL',
            ]);
        }

        // server
        if ($isNitro) {
            $this->server = '127.0.0.1';
        } else {
            $this->server = $this->prompt('Database server name or IP address:', [
                'required' => true,
                'default' => $this->server ?: '127.0.0.1',
            ]);
            $this->server = strtolower($this->server);
        }

        // port
        $this->port = (int)$this->prompt('Database port:', [
            'required' => true,
            'default' => $this->port ?: ($this->driver === Connection::DRIVER_MYSQL ? 3306 : 5432),
            'validator' => function(string $input): bool {
                return is_numeric($input);
            }
        ]);

        userCredentials:

        // user & password
        if ($isNitro) {
            $this->user = 'nitro';
            $this->password = 'nitro';
        } else {
            $this->user = $this->prompt('Database username:', [
                'default' => $this->user ?: null,
            ]);

            if ($this->interactive) {
                $this->stdout('Database password: ');
                $this->password = CliPrompt::hiddenPrompt(true);
            }
        }

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
            'default' => $this->database ?: null,
        ]);

        // schema
        if ($this->driver === Connection::DRIVER_PGSQL) {
            $this->schema = $this->prompt('Database schema:', [
                'required' => true,
                'default' => $this->schema ?: 'public',
            ]);
        }

        // tablePrefix
        $this->tablePrefix = $this->prompt('Database table prefix' . ($this->tablePrefix ? ' (type "none" for none)' : '') . ':', [
            'default' => $this->tablePrefix ?: null,
            'validator' => function(string $input): bool {
                if (strlen(StringHelper::ensureRight($input, '_')) > 6) {
                    $this->stderr('The table prefix must be 5 or less characters long.' . PHP_EOL, Console::FG_RED);
                    return false;
                }
                return true;
            }
        ]);
        if ($this->tablePrefix && $this->tablePrefix !== 'none') {
            $this->tablePrefix = StringHelper::ensureRight($this->tablePrefix, '_');
        } else {
            $this->tablePrefix = '';
        }

        // Test the DB connection
        $this->stdout('Testing database credentials ... ', Console::FG_YELLOW);

        try {
            $dbConfig = Craft::$app->getConfig()->getDb();
        } catch (InvalidConfigException $e) {
            $dbConfig = new DbConfig();
        }

        test:

        $dbConfig->driver = $this->driver;
        $dbConfig->server = $this->server;
        $dbConfig->port = $this->port;
        $dbConfig->database = $this->database;
        $dbConfig->dsn = "{$this->driver}:host={$this->server};port={$this->port};dbname={$this->database};";
        $dbConfig->user = $this->user;
        $dbConfig->password = $this->password;
        $dbConfig->schema = $this->schema;
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
            /** @var \PDOException $pdoException */
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
                strpos($message, 'Access denied for user') !== false ||
                strpos($message, 'no password supplied') !== false ||
                strpos($message, 'password authentication failed for user') !== false
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

        Craft::$app->setIsInstalled(null);

        $this->stdout('success!' . PHP_EOL, Console::FG_GREEN);
        $this->stdout('Saving database credentials to your .env file ... ', Console::FG_YELLOW);

        // If there's a DB_DSN environment variable, go with that
        if (App::env('DB_DSN') !== false) {
            if (!$this->_setEnvVar('DB_DSN', $dbConfig->dsn)) {
                return ExitCode::UNSPECIFIED_ERROR;
            }
        } else if (
            !$this->_setEnvVar('DB_DRIVER', $this->driver) ||
            !$this->_setEnvVar('DB_SERVER', $this->server) ||
            !$this->_setEnvVar('DB_PORT', $this->port) ||
            !$this->_setEnvVar('DB_DATABASE', $this->database)
        ) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (
            !$this->_setEnvVar('DB_USER', $this->user) ||
            !$this->_setEnvVar('DB_PASSWORD', $this->password) ||
            !$this->_setEnvVar('DB_SCHEMA', $this->schema) ||
            !$this->_setEnvVar('DB_TABLE_PREFIX', $this->tablePrefix)
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
    private function _outputCommand(string $command)
    {
        $script = FileHelper::normalizePath($this->request->getScriptFile());
        if (!Platform::isWindows() && ($home = App::env('HOME')) !== false) {
            $home = FileHelper::normalizePath($home);
            if (strpos($script, $home . DIRECTORY_SEPARATOR) === 0) {
                $script = '~' . substr($script, strlen($home));
            }
        }
        $this->stdout(PHP_EOL . '    php ' . $script . ' ' . $command . PHP_EOL . PHP_EOL);
    }

    /**
     * Sets an environment variable value in the project's .env file.
     *
     * @param $name
     * @param $value
     * @return bool
     */
    private function _setEnvVar($name, $value): bool
    {
        $configService = Craft::$app->getConfig();
        $path = $configService->getDotEnvPath();

        if (!file_exists($path)) {
            if ($this->confirm(PHP_EOL . "A .env file doesn't exist at {$path}. Would you like to create one?", true)) {
                try {
                    FileHelper::writeToFile($path, '');
                } catch (\Throwable $e) {
                    $this->stderr("Unable to create {$path}: {$e->getMessage()}" . PHP_EOL, Console::FG_RED);
                    return false;
                }

                $this->stdout("{$path} created. Note you still need to set up PHP dotenv for its values to take effect." . PHP_EOL, Console::FG_YELLOW);
            } else {
                $this->stdout(PHP_EOL . 'Action aborted.' . PHP_EOL, Console::FG_YELLOW);
                return false;
            }
        }

        try {
            $configService->setDotEnvVar($name, $value);
        } catch (\Throwable $e) {
            $this->stderr("Unable to set {$name} on {$path}: {$e->getMessage()}" . PHP_EOL, Console::FG_RED);
            return false;
        }

        return true;
    }
}
