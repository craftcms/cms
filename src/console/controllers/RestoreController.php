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
 * @since 3.1.29
 */
class RestoreController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'db';

    /**
     * Restores a database backup
     *
     * @param string|null The path to the database backup file.
     * @return int
     */
    public function actionDb(string $path = null): int
    {
        if (!is_file($path)) {
            $this->stderr('Backup file doesn\'t exist: ' . $path);
            return ExitCode::UNSPECIFIED_ERROR;
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
        return ExitCode::OK;
    }
}
