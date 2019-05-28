<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Creates a new database backup
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.21
 */
class BackupController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'db';

    /**
     * Creates a new database backup
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
    public function actionDb(string $path = null): int
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
            } else if (is_file($path)) {
                if (!$this->confirm("{$path} already exists. Overwrite?")) {
                    $this->stdout('Aborting' . PHP_EOL);
                    return ExitCode::OK;
                }
                unlink($path);
            }
        } else {
            $path = $db->getBackupFilePath();
        }

        try {
            $db->backupTo($path);
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
}
