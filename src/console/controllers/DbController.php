<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\db\Query;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use yii\console\ExitCode;
use yii\db\Exception;

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
    public $zip = false;

    /**
     * @var bool Whether to overwrite an existing backup file, if a specific file path is given.
     */
    public $overwrite = false;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);

        if ($actionID === 'backup') {
            $options[] = 'zip';
            $options[] = 'overwrite';
        }

        return $options;
    }

    /**
     * Creates a new database backup.
     *
     * @param string|null The path the database backup should be created at.
     * Can be any of the following:
     *
     * - A full file path
     * - A folder path (backup will be saved in there with a dynamically-generated name)
     * - A filename (backup will be saved in the working directory with the given name)
     * - Blank (backup will be saved to the config/backups/ folder with a dynamically-generated name)
     *
     * @return int
     */
    public function actionBackup(string $path = null): int
    {
        $this->stdout('Backing up the database ... ');
        $db = Craft::$app->getDb();

        if ($path !== null) {
            // Prefix with the working directory if a relative path or no path is given
            if (strpos($path, '.') === 0 || strpos(FileHelper::normalizePath($path, '/'), '/') === false) {
                $path = getcwd() . DIRECTORY_SEPARATOR . $path;
            }

            $path = FileHelper::normalizePath($path);

            if (is_dir($path)) {
                $path .= DIRECTORY_SEPARATOR . basename($db->getBackupFilePath());
            } else if ($this->zip) {
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
                    if (!$this->confirm("$checkPath already exists. Overwrite?")) {
                        if ($this->interactive) {
                            $this->stdout('Aborting' . PHP_EOL);
                            return ExitCode::OK;
                        }
                        $this->stderr("$checkPath already exists. Retry with the --overwire flag to overwrite it." . PHP_EOL, Console::FG_RED);
                        return ExitCode::UNSPECIFIED_ERROR;
                    }
                }
                unlink($checkPath);
            }
        }

        try {
            $db->backupTo($path);
            if ($this->zip) {
                $zipPath = FileHelper::zip($path);
                unlink($path);
                $path = $zipPath;
            }
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        $size = Craft::$app->getFormatter()->asShortSize(filesize($path));
        $this->stdout("Backup file: {$path} ({$size})" . PHP_EOL);
        return ExitCode::OK;
    }

    /**
     * Restores a database backup.
     *
     * @param string|null The path to the database backup file.
     * @return int
     */
    public function actionRestore(string $path = null): int
    {
        if (!is_file($path)) {
            $this->stderr("Backup file doesn't exist: $path" . PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'zip') {
            $zip = new \ZipArchive();

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

        $this->stdout('Restoring database backup ... ');

        try {
            Craft::$app->getDb()->restore($path);
        } catch (\Throwable $e) {
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

        return ExitCode::OK;
    }

    /**
     * Converts tables’ character sets and collations. (MySQL only)
     *
     * @param string|null $charset The character set
     * @param string|null $collation
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
                'default' => $dbConfig->charset ?? 'utf8',
            ]);
        }

        if ($collation === null) {
            $collation = $this->prompt('Which collation should be used?', [
                'default' => $dbConfig->collation ?? 'utf8_unicode_ci',
            ]);
        }

        foreach ($tableNames as $tableName) {
            $tableName = $schema->getRawTableName($tableName);
            $this->stdout('Converting ');
            $this->stdout($tableName, Console::FG_CYAN);
            $this->stdout(' ... ');
            $db->createCommand("ALTER TABLE `$tableName` CONVERT TO CHARACTER SET $charset COLLATE $collation")->execute();
            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        }

        $this->stdout("Finished converting tables to $charset/$collation." . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
