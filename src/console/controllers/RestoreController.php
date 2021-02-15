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
 * DEPRECATED. Use `db/restore` instead.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.29
 * @deprecated in 3.6.0. Use the `db/restore` command instead.
 */
class RestoreController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'db';

    /**
     * DEPRECATED. Use `db/restore` instead.
     *
     * @param string|null The path to the database backup file.
     * @return int
     */
    public function actionDb(string $path = null): int
    {
        Console::outputWarning("The restore command is deprecated.\nRunning db/restore instead...");
        return Craft::$app->runAction('db/restore', func_get_args());
    }
}
