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
use craft\db\Connection;
use craft\errors\DbConnectException;
use craft\helpers\App;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use Seld\CliPrompt\CliPrompt;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Craft CMS setup installer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SetupController extends Controller
{
    // Properties
    // =========================================================================

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
    public $user;
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

    // Public Methods
    // =========================================================================

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
        if (!Craft::$app->getConfig()->getGeneral()->securityKey) {
            $this->run('security-key');
            $this->stdout(PHP_EOL);
        }

        if (!$this->interactive) {
            return ExitCode::OK;
        }

        $this->run('db-creds');

        if (Craft::$app->getIsInstalled()) {
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
        $this->run('security-key');
        $this->stdout(PHP_EOL . 'Welcome to Craft CMS! Run the following command if you want to setup Craft from your terminal:' . PHP_EOL);
        $this->_outputCommand('setup');
        return ExitCode::OK;
    }

    /**
     * Generates a new security key and saves it in the .env file.
     *
     * @return int
     */
    public function actionSecurityKey(): int
    {
        $this->stdout(PHP_EOL . 'Generating a security key... ', Console::FG_YELLOW);
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
        try {
            $dbConfig = Craft::$app->getConfig()->getDb();
        } catch (InvalidConfigException $e) {
            $dbConfig = new DbConfig();
        }

        $firstTime = true;

        top:

        // driver
        if ($this->driver) {
            if (!in_array($this->driver, [DbConfig::DRIVER_MYSQL, DbConfig::DRIVER_PGSQL], true)) {
                $this->stderr('--driver must be either "' . DbConfig::DRIVER_MYSQL . '" or "' . DbConfig::DRIVER_PGSQL . '".' . PHP_EOL, Console::FG_RED);
                return ExitCode::USAGE;
            }
            $dbConfig->driver = $this->driver;
        } else if ($this->interactive) {
            $dbConfig->driver = $this->select('Which database driver are you using?', [
                DbConfig::DRIVER_MYSQL => 'MySQL',
                DbConfig::DRIVER_PGSQL => 'PostgreSQL',
            ]);
        }

        // server
        if ($this->server) {
            $server = $this->server;
        } else {
            $server = $this->prompt('Database server name or IP address:', [
                'required' => true,
                'default' => $dbConfig->server,
            ]);
        }
        $dbConfig->server = strtolower($server);

        // port
        if ($this->port) {
            $dbConfig->port = $this->port;
        } else {
            if ($firstTime) {
                $defaultPort = $dbConfig->driver === DbConfig::DRIVER_MYSQL ? 3306 : 5432;
            } else {
                $defaultPort = $dbConfig->port;
            }
            $dbConfig->port = $this->prompt('Database port:', [
                'required' => true,
                'default' => $defaultPort,
                'validator' => function(string $input): bool {
                    return is_numeric($input);
                }
            ]);
        }

        // user
        if ($this->user) {
            $dbConfig->user = $this->user;
        } else {
            $dbConfig->user = $this->prompt('Database username:', [
                'default' => $dbConfig->user,
            ]);
        }

        // password
        if ($this->password) {
            $dbConfig->password = $this->password;
        } else if ($this->interactive) {
            $this->stdout('Database password: ');
            $dbConfig->password = CliPrompt::hiddenPrompt(true);
        }

        // database
        if ($this->database) {
            $dbConfig->database = $this->database;
        } else if ($this->interactive || $dbConfig->database) {
            $dbConfig->database = $this->prompt('Database name:', [
                'required' => true,
                'default' => $dbConfig->database,
            ]);
        } else {
            $this->stderr('The --database option must be set.' . PHP_EOL, Console::FG_RED);
            return ExitCode::USAGE;
        }

        // schema
        if ($dbConfig->driver === DbConfig::DRIVER_PGSQL) {
            if ($this->schema) {
                $dbConfig->schema = $this->schema;
            } else {
                $dbConfig->schema = $this->prompt('Database schema:', [
                    'required' => true,
                    'default' => $dbConfig->schema,
                ]);
            }
        }

        // tablePrefix
        if ($this->tablePrefix) {
            $tablePrefix = $this->tablePrefix;
        } else {
            $tablePrefix = $this->prompt('Database table prefix' . ($dbConfig->tablePrefix ? ' (type "none" for none)' : '') . ':', [
                'default' => $dbConfig->tablePrefix,
                'validator' => function(string $input): bool {
                    if (strlen(StringHelper::ensureRight($input, '_')) > 6) {
                        Console::stderr($this->ansiFormat('The table prefix must be 5 or less characters long.' . PHP_EOL, Console::FG_RED));
                        return false;
                    }
                    return true;
                }
            ]);
        }
        if ($tablePrefix && $tablePrefix !== 'none') {
            $dbConfig->tablePrefix = StringHelper::ensureRight($tablePrefix, '_');
        } else {
            $tablePrefix = $dbConfig->tablePrefix = '';
        }

        // Test the DB connection
        $this->stdout('Testing database credentials... ', Console::FG_YELLOW);
        $dbConfig->updateDsn();
        /** @var Connection $db */
        $db = Craft::createObject(App::dbConfig($dbConfig));

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
            //$this->stdout(VarDumper::dumpAsString($e->getPrevious()));
            $firstTime = false;

            if (!$this->interactive) {
                return ExitCode::UNSPECIFIED_ERROR;
            }

            goto top;
        }

        Craft::$app->set('db', $db);
        Craft::$app->setIsInstalled(null);

        $this->stdout('success!' . PHP_EOL, Console::FG_GREEN);
        $this->stdout('Saving database credentials to your .env file... ', Console::FG_YELLOW);

        if (
            !$this->_setEnvVar('DB_DRIVER', $dbConfig->driver) ||
            !$this->_setEnvVar('DB_SERVER', $dbConfig->server) ||
            !$this->_setEnvVar('DB_PORT', $dbConfig->port) ||
            !$this->_setEnvVar('DB_USER', $dbConfig->user) ||
            !$this->_setEnvVar('DB_PASSWORD', $dbConfig->password) ||
            !$this->_setEnvVar('DB_DATABASE', $dbConfig->database) ||
            !$this->_setEnvVar('DB_SCHEMA', $dbConfig->schema) ||
            !$this->_setEnvVar('DB_TABLE_PREFIX', $tablePrefix)
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

    // Private Methods
    // =========================================================================

    /**
     * Outputs a terminal command.
     *
     * @param string $command
     */
    private function _outputCommand(string $command)
    {
        $script = FileHelper::normalizePath(Craft::$app->getRequest()->getScriptFile());
        if (!Platform::isWindows() && ($home = getenv('HOME')) !== false) {
            $home = FileHelper::normalizePath($home);
            if (strpos($script, $home . DIRECTORY_SEPARATOR) === 0) {
                $script = '~' . substr($script, strlen($home));
            }
        }
        $this->stdout(PHP_EOL . '    ' . $script . ' ' . $command . PHP_EOL . PHP_EOL);
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
