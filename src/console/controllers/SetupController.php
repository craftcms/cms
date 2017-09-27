<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\console\controllers;

use Composer\Util\Platform;
use Craft;
use craft\config\DbConfig;
use craft\db\Connection;
use craft\errors\DbConnectException;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use Seld\CliPrompt\CliPrompt;
use yii\console\Controller;

/**
 * Craft CMS setup installer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SetupController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Sets up all the things.
     */
    public function actionIndex()
    {
        if (!Craft::$app->getConfig()->getGeneral()->securityKey) {
            $this->run('security-key');
            $this->stdout(PHP_EOL);
        }

        $this->run('db-creds');

        if (Craft::$app->getIsInstalled()) {
            $this->stdout("It looks like Craft is already installed, so we're done here.".PHP_EOL, Console::FG_YELLOW);
            return;
        }

        if (!$this->confirm(PHP_EOL.'Install Craft now?', true)) {
            $this->stdout("You can install Craft from a browser once you've set up a web server, or by running this command:".PHP_EOL);
            $this->_outputCommand('install');
            return;
        }

        $this->stdout(PHP_EOL);
        $this->module->runAction('install');
    }

    /**
     * Called from the post-create-project-cmd Composer hook.
     */
    public function actionWelcome()
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
        $this->stdout(PHP_EOL.'Welcome to Craft CMS! Run the following command if you want to setup Craft from your terminal:'.PHP_EOL);
        $this->_outputCommand('setup');
    }

    /**
     * Generates a new security key and saves it in the .env file.
     */
    public function actionSecurityKey()
    {
        $this->stdout(PHP_EOL.'Generating a security key...'.PHP_EOL, Console::FG_YELLOW);
        $key = Craft::$app->getSecurity()->generateRandomString();
        if ($this->_setEnvVar('SECURITY_KEY', $key)) {
            Craft::$app->getConfig()->getGeneral()->securityKey = $key;
            $this->stdout("done ({$key})".PHP_EOL, Console::FG_YELLOW);
        }
    }

    /**
     * Stores new DB connection settings to the .env file.
     */
    public function actionDbCreds()
    {
        $dbConfig = Craft::$app->getConfig()->getDb();
        $firstTime = true;

        top:
        $dbConfig->driver = $this->select('Which database driver are you using?', [
            DbConfig::DRIVER_MYSQL => 'MySQL',
            DbConfig::DRIVER_PGSQL => 'PostgreSQL',
        ]);
        $server = $this->prompt('Database server name or IP address:', [
            'required' => true,
            'default' => $dbConfig->server,
        ]);
        $dbConfig->server = strtolower($server);
        $dbConfig->port = $this->prompt('Database port:', [
            'required' => true,
            'default' => $firstTime ? ($dbConfig->driver === DbConfig::DRIVER_MYSQL ? 3306 : 5432) : $dbConfig->port,
            'validator' => function(string $input): bool {
                return is_numeric($input);
            }
        ]);
        $dbConfig->user = $this->prompt('Database username:', [
            'default' => $dbConfig->user,
        ]);
        $this->stdout('Database password: ');
        $dbConfig->password = CliPrompt::hiddenPrompt();
        $dbConfig->database = $this->prompt('Database name:', [
            'required' => true,
            'default' => $dbConfig->database,
        ]);
        if ($dbConfig->driver === DbConfig::DRIVER_PGSQL) {
            $dbConfig->schema = $this->prompt('Database schema:', [
                'required' => true,
                'default' => $dbConfig->schema,
            ]);
        }
        $tablePrefix = $this->prompt('Database table prefix'.($dbConfig->tablePrefix ? ' (type "none" for none)' : '').':', [
            'default' => $dbConfig->tablePrefix,
            'validator' => function(string $input): bool {
                if (strlen(StringHelper::ensureRight($input, '_')) > 6) {
                    Console::stderr($this->ansiFormat('The table prefix must be 5 or less characters long.'.PHP_EOL, Console::FG_RED));
                    return false;
                }
                return true;
            }
        ]);
        if ($tablePrefix && $tablePrefix !== 'none') {
            $dbConfig->tablePrefix = StringHelper::ensureRight($tablePrefix, '_');
        } else {
            $tablePrefix = $dbConfig->tablePrefix = '';
        }

        // Test the DB connection
        $this->stdout('Testing database credentials... ', Console::FG_YELLOW);
        $dbConfig->updateDsn();
        if ($dbConfig->driver === DbConfig::DRIVER_MYSQL) {
            $schemaClass = \craft\db\mysql\Schema::class;
        } else {
            $schemaClass = \craft\db\pgsql\Schema::class;
        }
        /** @var Connection $db */
        $db = Craft::createObject([
            'class' => Connection::class,
            'driverName' => $dbConfig->driver,
            'dsn' => $dbConfig->dsn,
            'username' => $dbConfig->user,
            'password' => $dbConfig->password,
            'charset' => $dbConfig->charset,
            'tablePrefix' => $dbConfig->tablePrefix,
            'schemaMap' => [
                $dbConfig->driver => [
                    'class' => $schemaClass,
                ]
            ],
            'commandClass' => \craft\db\Command::class,
            'attributes' => $dbConfig->attributes,
        ]);

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
            $pdoException = $e->getPrevious()->getPrevious();
            $this->stderr('failed: '.$pdoException->getMessage().PHP_EOL, Console::FG_RED);
            //$this->stdout(VarDumper::dumpAsString($e->getPrevious()));
            $firstTime = false;
            goto top;
        }

        Craft::$app->set('db', $db);
        Craft::$app->setIsInstalled(null);

        $this->stdout('success!'.PHP_EOL, Console::FG_GREEN);
        $this->stdout('Saving database credentials to your .env file...'.PHP_EOL, Console::FG_YELLOW);

        if (
            $this->_setEnvVar('DB_DRIVER', $dbConfig->driver) &&
            $this->_setEnvVar('DB_SERVER', $dbConfig->server) &&
            $this->_setEnvVar('DB_PORT', $dbConfig->port) &&
            $this->_setEnvVar('DB_USER', $dbConfig->user) &&
            $this->_setEnvVar('DB_PASSWORD', $dbConfig->password) &&
            $this->_setEnvVar('DB_DATABASE', $dbConfig->database) &&
            $this->_setEnvVar('DB_SCHEMA', $dbConfig->schema) &&
            $this->_setEnvVar('DB_TABLE_PREFIX', $tablePrefix)
        ) {
            $this->stdout('done'.PHP_EOL, Console::FG_YELLOW);
        }
    }

    /**
     * Outputs a terminal command.
     */
    private function _outputCommand(string $command)
    {
        $script = FileHelper::normalizePath(Craft::$app->getRequest()->getScriptFile());
        if (!Platform::isWindows() && ($home = getenv('HOME')) !== false) {
            $home = FileHelper::normalizePath($home);
            if (strpos($script, $home.DIRECTORY_SEPARATOR) === 0) {
                $script = '~'.substr($script, strlen($home));
            }
        }
        $this->stdout(PHP_EOL.'    '.$script.' '.$command.PHP_EOL.PHP_EOL);
    }

    /**
     * Sets an environment variable value in the project's .env file.
     *
     * @param $name
     * @param $value
     *
     * @return bool
     */
    private function _setEnvVar($name, $value): bool
    {
        $path = Craft::getAlias('@root/.env');
        if (!file_exists($path)) {
            if ($this->confirm("A .env file doesn't exist at {$path}. Would you like to create one?", true)) {
                FileHelper::writeToFile($path, "{$name}=".PHP_EOL);
                $this->stdout("{$path} created. Note you still need to set up PHP dotenv for its values to take effect.".PHP_EOL, Console::FG_YELLOW);
            } else {
                $this->stdout('Action aborted.'.PHP_EOL, Console::FG_YELLOW);
                return false;
            }
        }

        $contents = file_get_contents($path);
        $qName = preg_quote($name, '/');
        $contents = preg_replace("/^(\s*){$qName}=.*/m", "\$1{$name}=\"{$value}\"", $contents, -1, $count);
        if ($count === 0) {
            if ($this->confirm("{$name} could not be found in {$path}. Would you like to add it?", true)) {
                $contents = rtrim($contents);
                $contents = ($contents ? $contents.PHP_EOL.PHP_EOL : '')."{$name}=\"{$value}\"".PHP_EOL;
            } else {
                $this->stdout('Action aborted.'.PHP_EOL, Console::FG_YELLOW);
                return false;
            }
        }

        FileHelper::writeToFile($path, $contents);
        return true;
    }
}
