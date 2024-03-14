<?php

namespace craft\db;

use Closure;
use Composer\Util\Platform;
use Craft;
use craft\base\Component;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use mikehaertl\shellcommand\Command as ShellCommand;
use PDO;
use yii\base\ErrorException;
use yii\base\Exception;

/**
 * @property-read string $execCommand
 * @property-read ShellCommand $command
 */
abstract class DbShellCommand extends Component
{
    public ?Closure $callback = null;

    protected function getCommand(): ShellCommand
    {
        return new ShellCommand();
    }

    public function getExecCommand(): string
    {
        return $this->getCommand()->getExecCommand();
    }

    /**
     * Creates a temporary my.cnf file based on the DB config settings.
     *
     * @return string The path to the my.cnf file
     * @throws ErrorException
     */
    protected function createDumpConfigFile(): string
    {
        if (!Craft::$app->getDb()->getIsMysql()) {
            throw new Exception('This method is only applicable to MySQL.');
        }

        $db = Craft::$app->getDb();

        // Set on the schema for later cleanup
        $tempMyCnfPath
            = $db->getSchema()->tempMyCnfPath
            = FileHelper::normalizePath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . StringHelper::randomString(12) . '.cnf';

        $parsed = Db::parseDsn($db->dsn);
        $username = $db->getIsPgsql() && !empty($parsed['user']) ? $parsed['user'] : $db->username;
        $password = $db->getIsPgsql() && !empty($parsed['password']) ? $parsed['password'] : $db->password;
        $contents = '[client]' . PHP_EOL .
            'user=' . $username . PHP_EOL .
            'password="' . addslashes($password) . '"';

        if (isset($parsed['unix_socket'])) {
            $contents .= PHP_EOL . 'socket=' . $parsed['unix_socket'];
        } else {
            $contents .= PHP_EOL . 'host=' . ($parsed['host'] ?? '') .
                PHP_EOL . 'port=' . ($parsed['port'] ?? '');
        }

        // Certificates
        if (isset($db->attributes[PDO::MYSQL_ATTR_SSL_CA])) {
            $contents .= PHP_EOL . 'ssl_ca=' . $db->attributes[PDO::MYSQL_ATTR_SSL_CA];
        }
        if (isset($db->attributes[PDO::MYSQL_ATTR_SSL_CERT])) {
            $contents .= PHP_EOL . 'ssl_cert=' . $db->attributes[PDO::MYSQL_ATTR_SSL_CERT];
        }
        if (isset($db->attributes[PDO::MYSQL_ATTR_SSL_KEY])) {
            $contents .= PHP_EOL . 'ssl_key=' . $db->attributes[PDO::MYSQL_ATTR_SSL_KEY];
        }

        FileHelper::writeToFile($tempMyCnfPath, '');
        // Avoid a “world-writable config file 'my.cnf' is ignored” warning
        chmod($tempMyCnfPath, 0600);
        FileHelper::writeToFile($tempMyCnfPath, $contents, ['append']);

        return $tempMyCnfPath;
    }

    /**
     * Returns the PGPASSWORD command for backup/restore actions.
     *
     * @return string
     */
    protected function pgPasswordCommand(): string
    {
        if (!Craft::$app->getDb()->getIsPgsql()) {
            throw new Exception('This method is only applicable to PostgreSQL.');
        }

        return Platform::isWindows() ? 'set PGPASSWORD="{password}" && ' : 'PGPASSWORD="{password}" ';
    }
}
