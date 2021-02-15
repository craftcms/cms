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

/**
 * DEPRECATED. Use `db/backup` instead.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.21
 * @deprecated in 3.6.0. Use the `db/backup` command instead.
 */
class BackupController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'db';

    /**
     * @var bool Whether the backup should be saved as a zip file.
     * @since 3.5.0
     */
    public $zip = false;

    /**
     * @var bool Whether to overwrite an existing backup file, if a specific file path is given.
     * @since 3.5.0
     */
    public $overwrite = false;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'zip';
        $options[] = 'overwrite';
        return $options;
    }

    /**
     * DEPRECATED. Use `db/backup` instead.
     *
     * @return int
     */
    public function actionDb(string $path = null): int
    {

        Console::outputWarning("The backup command is deprecated.\nRunning db/backup instead...");
        return Craft::$app->runAction('db/backup', func_get_args() + [
                'zip' => $this->zip,
                'overwrite' => $this->overwrite,
            ]);
    }
}
