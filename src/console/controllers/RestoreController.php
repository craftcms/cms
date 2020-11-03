<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use yii\console\ExitCode;

/**
 * Restores a database from backup.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.29
 */
class RestoreController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'db';

    /**
     * Allows you to restore a database from a backup.
     *
     * @param string|null The path to the database backup file.
     * @return int
     */
    public function actionDb(string $path = null): int
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
}
