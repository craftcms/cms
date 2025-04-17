<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\db\Connection;
use craft\db\Table;
use craft\helpers\Console;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use Throwable;
use yii\base\NotSupportedException;
use yii\console\ExitCode;
use yii\db\Exception;
use ZipArchive;

/**
 * Performs database operations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class DbController extends Controller
{
    /**
     * @var bool Whether the backup should be saved as a zip file.
     */
    public bool $zip = false;

    /**
     * @var string|null The output format that should be used (`custom`, `directory`, `tar`, or `plain`).
     *
     * The `backupCommandFormat` config setting will be used by default.
     *
     * @since 5.2.0
     */
    public ?string $format = null;

    /**
     * @var bool Whether to overwrite an existing backup file, if a specific file path is given.
     */
    public bool $overwrite = false;

    /**
     * @var bool Whether to drop all preexisting tables in the database prior to restoring the backup.
     * @since 4.1.0
     */
    public bool $dropAllTables = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        switch ($actionID) {
            case 'backup':
                $options[] = 'zip';
                $options[] = 'overwrite';

                if (Craft::$app->getDb()->getIsPgsql()) {
                    $options[] = 'format';
                }
                break;
            case 'restore':
                $options[] = 'dropAllTables';

                if (Craft::$app->getDb()->getIsPgsql()) {
                    $options[] = 'format';
                }
                break;
        }

        return $options;
    }

    /**
     * Repairs all tables in the database.
     *
     * Note that this can cause table locking, which could interfere with SQL being executed.
     *
     * @since 5.7.0
     * @see https://dev.mysql.com/doc/refman/8.4/en/optimize-table.html
     * @see https://www.postgresql.org/docs/current/sql-analyze.html
     */
    public function actionRepair(): int
    {
        if (!$this->_tablesExist()) {
            $this->stdout('No existing database tables found.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        if ($this->interactive && !$this->confirm('Are you sure you want to repair all tables from the database?')) {
            $this->stdout('Aborted.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->_backupPrompt();

        try {
            $this->_repairAllTables();
        } catch (Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Drops all tables in the database.
     *
     * Example:
     * ```
     * php craft db/drop-all-tables
     * ```
     *
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\Exception
     * @since 4.1.0
     */
    public function actionDropAllTables(): int
    {
        if (!$this->_tablesExist()) {
            $this->stdout('No existing database tables found.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        if ($this->interactive && !$this->confirm('Are you sure you want to drop all tables from the database?')) {
            $this->stdout('Aborted.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->_backupPrompt();

        try {
            $this->_dropAllTables();
        } catch (Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Returns whether any database tables exist currently.
     *
     * @return bool
     */
    private function _tablesExist(): bool
    {
        return !empty(Craft::$app->getDb()->getSchema()->getTableNames());
    }

    /**
     * Prompts for whether the database should be backed up.
     */
    private function _backupPrompt(): void
    {
        if ($this->interactive && $this->confirm('Backup your database?')) {
            $this->runAction('backup');
            $this->stdout(PHP_EOL);
        }
    }

    /**
     * Repairs all tables in the database.
     *
     * @throws NotSupportedException
     * @throws Exception
     */
    private function _repairAllTables(): void
    {
        $db = Craft::$app->getDb();
        $tableNames = $db->getSchema()->getTableNames();

        $this->stdout('Repairing all database tables ... ' . PHP_EOL);

        if ($db->getIsMysql()) {
            $sql = 'OPTIMIZE TABLE [[%s]]';
        } else {
            $sql = 'ANALYZE VERBOSE [[%s]]';
        }

        foreach ($tableNames as $tableName) {
            $this->do("Repairing `$tableName`", function() use ($db, $sql, $tableName) {
                $db->createCommand(sprintf($sql, $tableName))->execute();
            });
        }

        $this->stdout('Finished repairing all database tables.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
    }

    /**
     * Drops all tables in the database.
     *
     * @throws NotSupportedException
     * @throws Exception
     */
    private function _dropAllTables(): void
    {
        $tableNames = Craft::$app->getDb()->getSchema()->getTableNames();

        $this->stdout('Dropping all database tables ... ' . PHP_EOL);

        foreach ($tableNames as $tableName) {
            $this->stdout('    - Dropping ');
            $this->stdout($tableName, Console::FG_CYAN);
            $this->stdout(' ... ');
            Db::dropAllForeignKeysToTable($tableName);
            Craft::$app->getDb()->createCommand()
                ->dropTable($tableName)
                ->execute();
            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        }

        $this->stdout('Finished dropping all database tables.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
    }

    /**
     * Creates a new database backup.
     *
     * Example:
     * ```
     * php craft db/backup ./my-backups/
     * ```
     *
     * @param string|null $path The path the database backup should be created at.
     * Can be any of the following:
     *
     * - A full file path
     * - A folder path (backup will be saved in there with a dynamically-generated name)
     * - A filename (backup will be saved in the working directory with the given name)
     * - Blank (backup will be saved to the `storage/backups/` folder with a dynamically-generated name)
     *
     * @return int
     */
    public function actionBackup(?string $path = null): int
    {
        $this->stdout('Backing up the database ... ');
        $db = Craft::$app->getDb();

        if (isset($this->format) && $db->getIsPgsql()) {
            $db->getSchema()->setBackupFormat($this->format);
        }

        if ($path !== null) {
            // Prefix with the working directory if a relative path or no path is given
            if (str_starts_with($path, '.') || !str_contains(FileHelper::normalizePath($path, '/'), '/')) {
                $path = getcwd() . DIRECTORY_SEPARATOR . $path;
            }

            $path = FileHelper::normalizePath($path);

            if (is_dir($path)) {
                $path .= DIRECTORY_SEPARATOR . basename($db->getBackupFilePath());
            } elseif ($this->zip) {
                $path = preg_replace('/\.zip$/', '', $path);
            }
        } else {
            $path = $db->getBackupFilePath();
        }

        $checkPaths = [$path];
        if ($this->zip) {
            $checkPaths[] = "$path.zip";
        }

        foreach ($checkPaths as $checkPath) {
            if (is_file($checkPath)) {
                if (!$this->overwrite) {
                    if (!$this->interactive) {
                        $this->stderr("$checkPath already exists. Retry with the --overwrite flag to overwrite it." . PHP_EOL, Console::FG_RED);
                        return ExitCode::UNSPECIFIED_ERROR;
                    }
                    if (!$this->confirm("$checkPath already exists. Overwrite?")) {
                        $this->stdout('Aborting' . PHP_EOL);
                        return ExitCode::OK;
                    }
                }
                unlink($checkPath);
            }
        }

        try {
            $db->backupTo($path);
            if ($this->zip) {
                $zipPath = FileHelper::zip($path);
                FileHelper::unlink($path);
                $path = $zipPath;
            }
        } catch (Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

        if (is_dir($path)) {
            $this->stdout("Backup directory: $path" . PHP_EOL);
        } else {
            $size = Craft::$app->getFormatter()->asShortSize(filesize($path));
            $this->stdout("Backup file: $path ($size)" . PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * Restores a database backup.
     *
     * Example:
     * ```
     * php craft db/restore ./my-backup.sql
     * ```
     *
     * @param string|null $path The path to the database backup file.
     * @return int
     */
    public function actionRestore(?string $path = null): int
    {
        if (!is_readable($path)) {
            $this->stderr("Backup path doesn't exist: $path" . PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'zip') {
            $zip = new ZipArchive();

            if ($zip->open($path) !== true) {
                $this->stderr("Unable to open the zip file at $path." . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $tempDir = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . StringHelper::randomString(10);
            FileHelper::createDirectory($tempDir);
            $this->stdout("Extracting zip to a temp directory ... ");
            $zip->extractTo($tempDir);
            $zip->close();
            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

            // Find the first file in there
            $files = FileHelper::findFiles($tempDir);
            if (empty($files)) {
                $this->stderr("No files unzipped from $path." . PHP_EOL, Console::FG_RED);
            }

            $path = reset($files);
        }

        if ($this->_tablesExist()) {
            $this->_backupPrompt();

            if (
                $this->dropAllTables ||
                ($this->interactive && $this->confirm('Drop all tables from the database first?'))
            ) {
                try {
                    $this->_dropAllTables();
                } catch (Throwable $e) {
                    Craft::$app->getErrorHandler()->logException($e);
                    $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }
        }

        $this->stdout('Restoring database backup ... ');

        try {
            $db = Craft::$app->getDb();
            if ($db->getIsPgsql()) {
                $restoreFormat = $this->format ?? match (FileHelper::getMimeType($path)) {
                    'application/octet-stream' => 'custom',
                    'application/x-tar' => 'tar',
                    'directory' => 'directory',
                    default => null,
                };
                $db->getSchema()->setRestoreFormat($restoreFormat);
            }
            $db->restore($path);
        } catch (Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

        if (isset($tempDir)) {
            $this->stdout('Deleting the temp directory ... ');
            FileHelper::removeDirectory($tempDir);
            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        }

        if ($this->interactive && $this->confirm('Clear data caches?', true)) {
            $this->run('clear-caches/data');
        }

        return ExitCode::OK;
    }

    /**
     * Converts tables’ character sets and collations. (MySQL only)
     *
     * Example:
     * ```
     * php craft db/convert-charset utf8 utf8_unicode_ci
     * ```
     *
     * @param string|null $charset The target character set, which honors `DbConfig::$charset`
     *                               or defaults to `utf8`.
     * @param string|null $collation The target collation, which honors `DbConfig::$collation`
     *                               or defaults to `utf8_unicode_ci`.
     * @return int
     */
    public function actionConvertCharset(?string $charset = null, ?string $collation = null): int
    {
        $db = Craft::$app->getDb();

        if (!$db->getIsMysql()) {
            $this->stderr('This command is only available when using MySQL.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $schema = $db->getSchema();
        $tableNames = $schema->getTableNames();

        if (empty($tableNames)) {
            $this->stderr('Could not find any database tables.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $dbConfig = Craft::$app->getConfig()->getDb();

        if ($charset === null) {
            $charset = $this->prompt('Which character set should be used?', [
                'default' => $dbConfig->getCharset(),
            ]);
        }

        if ($collation === null) {
            $collation = $this->prompt('Which collation should be used?', [
                'default' => $dbConfig->collation ?? Db::defaultCollation($db),
            ]);
        }

        // Disable FK checks
        $queryBuilder = $db->getSchema()->getQueryBuilder();
        $db->createCommand($queryBuilder->checkIntegrity(false))->execute();

        foreach ($tableNames as $tableName) {
            $tableName = $schema->getRawTableName($tableName);
            $this->stdout('Converting ');
            $this->stdout($tableName, Console::FG_CYAN);
            $this->stdout(' ... ');
            $db->createCommand("ALTER TABLE `$tableName` CONVERT TO CHARACTER SET $charset COLLATE $collation")->execute();
            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        }

        $db->createCommand()->checkIntegrity(true)->execute();

        $this->stdout("Finished converting tables to $charset/$collation." . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Drops the database table prefix from all tables.
     *
     * @param string|null $prefix The current table prefix. If omitted, the prefix will be detected automatically.
     * @return int
     * @since 4.5.10
     */
    public function actionDropTablePrefix(?string $prefix = null): int
    {
        $db = Craft::$app->getDb();
        $prefix ??= $this->detectPrefix($db);

        if ($prefix === false) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $prefix = StringHelper::ensureRight($prefix, '_');

        $this->warning(<<<MD
All database tables named `$prefix*` will be renamed to drop the table prefix.
**This should NOT be run on production!**
MD);

        if (!$this->confirm('Are you sure you want to proceed?')) {
            return ExitCode::OK;
        }

        $tablePrefixBak = $db->tablePrefix;
        $db->tablePrefix = $prefix;
        $schema = $db->getSchema();
        $quotedPrefix = preg_quote($prefix, '/');

        foreach ($schema->getTableNames() as $oldName) {
            if (!str_starts_with($oldName, $prefix)) {
                continue;
            }

            $newName = preg_replace("/^$quotedPrefix/", '', $oldName);

            $this->do(
                $this->markdownToAnsi("Renaming `$oldName` to `$newName`"),
                function() use ($oldName, $newName, $db) {
                    Db::renameTable($oldName, $newName, $db);
                },
            );
        }

        $db->tablePrefix = $tablePrefixBak;

        $this->success('Database tables renamed.');

        if (Craft::$app->getConfig()->getDb()->tablePrefix) {
            $this->tip('Don’t forget to clear out your `tablePrefix` database setting.');
        }

        return ExitCode::OK;
    }

    private function detectPrefix(Connection $db): string|false
    {
        $this->stdout("Detecting the current table prefix …\n");

        $patterns = array_map(
            function(string $name) {
                // based on Schema::getRawTableName()
                $name = preg_replace('/\\{\\{(.*?)}}/', '\1', $name);
                $name = preg_replace_callback('/[^%]+/', fn($match) => preg_quote($match[0], '/'), $name);
                return sprintf('/^%s$/', str_replace('%', '(\w+)_', $name));
            },
            [Table::ELEMENTS, Table::ENTRIES, Table::INFO, Table::SECTIONS],
        );

        $foundPrefixes = [];

        foreach ($db->getSchema()->getTableNames() as $name) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $name, $match)) {
                    if (!isset($foundPrefixes[$match[1]])) {
                        $foundPrefixes[$match[1]] = 1;
                    } else {
                        $foundPrefixes[$match[1]]++;
                    }
                    break;
                }
            }
        }

        $possiblePrefixes = [];
        foreach ($foundPrefixes as $prefix => $count) {
            if ($count === count($patterns)) {
                $possiblePrefixes[] = $prefix;
            }
        }

        if (empty($possiblePrefixes)) {
            $this->stdout("No current table prefix appears to be in use.\n", Console::FG_RED);
            return false;
        }

        if (count($possiblePrefixes) === 1) {
            $prefix = reset($possiblePrefixes);
            $this->stdout($this->markdownToAnsi("`$prefix` detected.") . PHP_EOL);
            return $prefix;
        }

        if (!$this->interactive) {
            $this->stdout("Multiple table prefixes were detected. Run the command again with the current prefix specified.\n", Console::FG_RED);
            return false;
        }

        return $this->select(
            'Multiple table prefixes were detected. Which one should be removed?',
            array_combine($possiblePrefixes, $possiblePrefixes),
        );
    }
}
